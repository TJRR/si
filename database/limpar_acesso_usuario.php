<?php

/**
 * Desfaz as vinculacoes de ACESSO (Google e/ou Senha) de um usuario,
 * devolvendo-o ao estagio "Nenhum ainda" (coluna Acesso da tela de Usuarios -
 * ver app/Views/admin/usuarios.php). Uso tipico: setup de ambiente de teste,
 * pra repetir o fluxo de primeiro acesso (convite/definir senha ou login
 * Google) sem precisar excluir o usuario inteiro (ver excluir_usuario.php).
 *
 * NAO mexe em status (pendente/aprovado/rejeitado), ativo/suspenso, perfis
 * nem no vinculo com participante (usuario_participante) - so' a credencial
 * de login em si:
 *   - usuarios.senha_hash e usuarios.google_id voltam pra NULL;
 *   - qualquer token_senha tipo "definir" ainda pendente (nao usado, nao
 *     expirado) e' invalidado, pra nao sobrar um link antigo de "definir
 *     senha" ainda valido depois do reset (mesmo cuidado do reenvio manual
 *     de convite, ver AcessoParticipanteService::reenviarConvite).
 *
 * Uso (identifique por id, e-mail OU nome exato - se o nome bater com mais
 * de um usuario, o script lista os ids e para):
 *   php database/limpar_acesso_usuario.php --id=42
 *   php database/limpar_acesso_usuario.php --email=fulano@teste.com
 *   php database/limpar_acesso_usuario.php --nome="Fulano da Silva"
 *   ... --confirmar (em qualquer uma das formas acima, pra aplicar de verdade)
 *
 * Por padrao roda em modo consulta (dry-run): so mostra o que seria feito.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auditoria;
use App\Core\Database;
use App\Repositories\TokenSenhaRepository;
use App\Repositories\UsuarioRepository;

$confirmar = in_array('--confirmar', $argv, true);
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
    echo "Uso: php database/limpar_acesso_usuario.php --id=42 [--confirmar]\n";
    echo "  ou: php database/limpar_acesso_usuario.php --email=fulano@teste.com [--confirmar]\n";
    echo "  ou: php database/limpar_acesso_usuario.php --nome=\"Fulano da Silva\" [--confirmar]\n";
    exit(1);
}

$pdo = Database::conexao();
$usuarios = new UsuarioRepository();
$tokens = new TokenSenhaRepository();

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
$tinhaSenha = $usuario['senha_hash'] !== null;
$tinhaGoogle = $usuario['google_id'] !== null;

echo "Usuario: {$usuario['nome']} <{$usuario['email']}> (id $usuarioId)\n";
echo str_repeat('-', 60) . "\n";

if (!$tinhaSenha && !$tinhaGoogle) {
    echo "Este usuario ja esta no estagio de Acesso \"Nenhum ainda\" (sem senha e sem Google vinculados).\n";
    echo "Nada a fazer.\n";
    exit;
}

echo "Acesso atual: " . implode(' + ', array_filter([$tinhaSenha ? 'Senha' : null, $tinhaGoogle ? 'Google' : null])) . "\n";
echo "Apos o reset: Nenhum ainda\n\n";

$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS total FROM tokens_senha
     WHERE usuario_id = :usuario_id AND tipo = 'definir' AND usado_em IS NULL AND expira_em >= NOW()"
);
$stmt->execute(['usuario_id' => $usuarioId]);
$totalTokensPendentes = (int) $stmt->fetch()['total'];

echo "Sera(ao) desfeito(s):\n";
echo "  - usuarios.senha_hash: " . ($tinhaSenha ? 'SIM (volta pra NULL)' : 'ja era NULL') . "\n";
echo "  - usuarios.google_id: " . ($tinhaGoogle ? 'SIM (volta pra NULL)' : 'ja era NULL') . "\n";
echo "  - tokens_senha (tipo \"definir\") pendente(s): $totalTokensPendentes invalidado(s)\n";

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi alterado.\n";
    echo "Para aplicar de verdade, repita o comando com --confirmar.\n";
    exit;
}

$antes = $usuario;
unset($antes['senha_hash']); // nunca guardar hash de senha em log de auditoria, mesmo com hash.

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('UPDATE usuarios SET senha_hash = NULL, google_id = NULL WHERE id = :id');
    $stmt->execute(['id' => $usuarioId]);

    $tokens->invalidarPendentes($usuarioId, 'definir');

    Auditoria::registrar(
        'limpar_acesso',
        'usuarios',
        $usuarioId,
        $antes,
        ['senha_hash' => null, 'google_id' => null],
        'Acesso (Google/Senha) resetado para "Nenhum ainda" via CLI (database/limpar_acesso_usuario.php)'
    );

    $pdo->commit();
} catch (\Throwable $e) {
    $pdo->rollBack();
    echo "\nErro ao resetar acesso: " . $e->getMessage() . "\nNada foi alterado (transacao revertida).\n";
    exit(1);
}

echo "\nAcesso de {$usuario['nome']} <{$usuario['email']}> (id $usuarioId) resetado para \"Nenhum ainda\".\n";
echo "Use o botao \"Reenviar convite\" na tela de Usuarios (ou login com Google) pra gerar um novo acesso.\n";
