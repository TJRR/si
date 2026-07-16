<?php

/**
 * Exclui uma conta de usuario (tabela `usuarios`) POR COMPLETO, junto com
 * tudo que so existe em funcao dela (perfis, vinculo com participante,
 * tokens de senha, notificacoes do painel, categorias de avaliador). Nao
 * existe tela nenhuma pra isso, de proposito - a tela de Usuarios so' tem
 * Suspender (ver decisao da Fase 14: excluir perfil foi tratado como
 * equivalente a "excluir usuario" e proibido na interface). Este script e'
 * a unica forma, restrita a quem tem acesso ao servidor/linha de comando.
 *
 * NAO apaga a inscricao/participante em si (tabelas participantes, equipes,
 * submissoes) - so' desfaz o vinculo de LOGIN (usuario_participante). A
 * pessoa continua existindo como participante inscrito, só perde a conta de
 * acesso ao sistema.
 *
 * Linhas que só fazem sentido junto com o usuário são REMOVIDAS:
 *   usuario_perfil_concurso, usuario_participante, tokens_senha,
 *   avaliador_categorias, notificacoes_painel, avaliador_designacoes (onde
 *   o usuario e' o avaliador designado), notas_lancadas (onde o usuario e'
 *   quem deu a nota).
 *
 * Linhas que registram uma ACAO desse usuario sobre OUTRA coisa (nao sao
 * "dele") tem só a referencia ANONIMIZADA (SET NULL), preservando o
 * historico: notificacoes.destinatario_usuario_id, log_auditoria.usuario_id,
 * avaliador_designacoes.atribuido_por, resultados_etapa.publicado_por,
 * resultados_trilha.publicado_por, equipe_participante.homologado_por.
 *
 * PROTECAO: se o usuario ja tiver notas lancadas (notas_lancadas), o
 * script RECUSA excluir mesmo com --confirmar, porque apagar essas notas
 * pode deixar resultados ja publicados (resultados_etapa/resultados_trilha,
 * nos modos de consolidacao "media_criterio"/"media_ne") desatualizados em
 * relacao as notas dos OUTROS avaliadores da mesma submissao, sem ninguem
 * perceber. Pra excluir mesmo assim, e preciso o operador confirmar que
 * sabe disso, com --forcar-com-notas (além de --confirmar) - e depois
 * conferir/republicar manualmente os resultados afetados (listados no
 * aviso).
 *
 * Uso (identifique por id, e-mail OU nome exato - se o nome bater com mais
 * de um usuario, o script lista os ids e para):
 *   php database/excluir_usuario.php --id=42
 *   php database/excluir_usuario.php --email=fulano@teste.com
 *   php database/excluir_usuario.php --nome="Fulano da Silva"
 *   ... --confirmar (em qualquer uma das formas acima, pra aplicar de verdade)
 *   ... --forcar-com-notas (junto com --confirmar, só se houver notas lancadas)
 *
 * Por padrao roda em modo consulta (dry-run): so mostra o que seria feito,
 * com a contagem de linhas afetadas em cada tabela relacionada.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auditoria;
use App\Core\Database;
use App\Repositories\UsuarioRepository;

$confirmar = in_array('--confirmar', $argv, true);
$forcarComNotas = in_array('--forcar-com-notas', $argv, true);
$id = null;
$email = null;
$nome = null;

foreach ($argv as $arg) {
    if (strpos($arg, '--id=') === 0) {
        $id = (int) substr($arg, strlen('--id='));
    } elseif (strpos($arg, '--email=') === 0) {
        $email = substr($arg, strlen('--email='));
    } elseif (strpos($arg, '--nome=') === 0) {
        $nome = substr($arg, strlen('--nome='));
    }
}

if ($id === null && ($email === null || $email === '') && ($nome === null || $nome === '')) {
    echo "Uso: php database/excluir_usuario.php --id=42 [--confirmar] [--forcar-com-notas]\n";
    echo "  ou: php database/excluir_usuario.php --email=fulano@teste.com [--confirmar] [--forcar-com-notas]\n";
    echo "  ou: php database/excluir_usuario.php --nome=\"Fulano da Silva\" [--confirmar] [--forcar-com-notas]\n";
    exit(1);
}

$pdo = Database::conexao();
$usuarios = new UsuarioRepository();

if ($id !== null) {
    $usuario = $usuarios->buscarPorId($id);
} elseif ($email !== null && $email !== '') {
    $usuario = $usuarios->buscarPorEmail($email);
} else {
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE nome = :nome');
    $stmt->execute(['nome' => $nome]);
    $encontrados = $stmt->fetchAll();

    if (count($encontrados) > 1) {
        echo "Mais de um usuario com o nome \"$nome\" - rode de novo com --id= usando um destes ids:\n";
        foreach ($encontrados as $item) {
            echo "  id {$item['id']} - {$item['email']}\n";
        }
        exit(1);
    }

    $usuario = !empty($encontrados) ? $encontrados[0] : null;
}

if ($usuario === null) {
    echo "Nenhum usuario encontrado.\n";
    exit(1);
}

$usuarioId = (int) $usuario['id'];

// Tabelas cuja linha SO' existe em funcao deste usuario - serao removidas.
$tabelasParaRemover = [
    'usuario_perfil_concurso' => 'usuario_id',
    'usuario_participante' => 'usuario_id',
    'tokens_senha' => 'usuario_id',
    'avaliador_categorias' => 'usuario_id',
    'notificacoes_painel' => 'usuario_id',
    'avaliador_designacoes' => 'usuario_id',
    'notas_lancadas' => 'usuario_id',
];

// Tabelas onde este usuario so' aparece como "quem fez a acao" sobre outra
// coisa - a coluna sera' anonimizada (SET NULL), a linha em si e' preservada.
$colunasParaAnonimizar = [
    'notificacoes' => 'destinatario_usuario_id',
    'log_auditoria' => 'usuario_id',
    'avaliador_designacoes' => 'atribuido_por',
    'resultados_etapa' => 'publicado_por',
    'resultados_trilha' => 'publicado_por',
    'equipe_participante' => 'homologado_por',
];

function contar($pdo, $tabela, $coluna, $usuarioId)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM `$tabela` WHERE `$coluna` = :usuario_id");
    $stmt->execute(['usuario_id' => $usuarioId]);

    return (int) $stmt->fetch()['total'];
}

echo "Usuario: {$usuario['nome']} <{$usuario['email']}> (id $usuarioId), status {$usuario['status']}\n";
echo str_repeat('-', 60) . "\n";
echo "Sera(ao) REMOVIDO(S) por completo:\n";

$totalNotasLancadas = 0;

foreach ($tabelasParaRemover as $tabela => $coluna) {
    $total = contar($pdo, $tabela, $coluna, $usuarioId);
    echo "  - $tabela: $total linha(s)\n";

    if ($tabela === 'notas_lancadas') {
        $totalNotasLancadas = $total;
    }
}

echo "\nSera(ao) ANONIMIZADO(S) (linha preservada, referencia ao usuario removida):\n";

foreach ($colunasParaAnonimizar as $tabela => $coluna) {
    $total = contar($pdo, $tabela, $coluna, $usuarioId);
    echo "  - $tabela.$coluna: $total linha(s)\n";
}

echo "\nPor fim, a propria linha em `usuarios` (id $usuarioId) sera removida.\n";

if ($totalNotasLancadas > 0) {
    echo "\n" . str_repeat('!', 60) . "\n";
    echo "ATENCAO: este usuario tem $totalNotasLancadas nota(s) lancada(s) como avaliador.\n";
    echo "Apagar essas notas pode deixar resultados JA PUBLICADOS (resultados_etapa/\n";
    echo "resultados_trilha) desatualizados em relacao as notas dos OUTROS avaliadores\n";
    echo "da mesma submissao, sem recalculo automatico. Confira/republique os resultados\n";
    echo "afetados manualmente depois de excluir.\n";
    echo str_repeat('!', 60) . "\n";

    if (!$forcarComNotas) {
        echo "\nExclusao RECUSADA por seguranca. Pra excluir mesmo assim, repita o comando\n";
        echo "acrescentando --forcar-com-notas (junto com --confirmar).\n";
        exit(1);
    }
}

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi alterado.\n";
    echo "Para aplicar de verdade, repita o comando com --confirmar";
    echo $totalNotasLancadas > 0 ? " --forcar-com-notas.\n" : ".\n";
    exit;
}

$antes = $usuario;
unset($antes['senha_hash']); // nunca guardar hash de senha em log de auditoria, mesmo com hash.

try {
    $pdo->beginTransaction();

    foreach ($tabelasParaRemover as $tabela => $coluna) {
        $stmt = $pdo->prepare("DELETE FROM `$tabela` WHERE `$coluna` = :usuario_id");
        $stmt->execute(['usuario_id' => $usuarioId]);
    }

    foreach ($colunasParaAnonimizar as $tabela => $coluna) {
        $stmt = $pdo->prepare("UPDATE `$tabela` SET `$coluna` = NULL WHERE `$coluna` = :usuario_id");
        $stmt->execute(['usuario_id' => $usuarioId]);
    }

    $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = :id');
    $stmt->execute(['id' => $usuarioId]);

    Auditoria::registrar('excluir', 'usuarios', $usuarioId, $antes, null, 'Excluido por completo via CLI (database/excluir_usuario.php)');

    $pdo->commit();
} catch (\Throwable $e) {
    $pdo->rollBack();
    echo "\nErro ao excluir: " . $e->getMessage() . "\nNada foi alterado (transacao revertida).\n";
    exit(1);
}

echo "\nUsuario {$usuario['nome']} <{$usuario['email']}> (id $usuarioId) excluido por completo.\n";
