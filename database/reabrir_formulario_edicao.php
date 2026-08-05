<?php

/**
 * Reabre um formulario dinamico para edicao (volta o status para
 * 'rascunho'), sem duplicar - use quando o formulario foi publicado mas
 * NUNCA recebeu nenhuma submissao real, tornando desnecessario o caminho
 * normal de "duplicar e editar a nova versao" (app/Views/admin/formularios/
 * campos.php so' libera edicao de campos quando status === 'rascunho';
 * 'despublicado' fica com a mesma trava de 'publicado' hoje).
 *
 * Mantem o MESMO formulario_dinamico_id - ao contrario de duplicar, nao
 * exige reapontar a Etapa pra um id novo depois.
 *
 * SEGURANCA: recusa reabrir se ja existir qualquer submissao real
 * (submissoes.formulario_dinamico_id) apontando pra este formulario -
 * nesse caso o caminho certo e' duplicar (tela Formularios), nunca editar
 * o formulario que already recebeu envio de equipe.
 *
 * Depois de reaberto: adicione o campo pela tela normal (Formularios >
 * Campos > Novo campo) e publique de novo (tela Formularios > Publicar).
 *
 * Uso:
 *   php database/reabrir_formulario_edicao.php --formulario-id=11               (dry-run)
 *   php database/reabrir_formulario_edicao.php --formulario-id=11 --confirmar   (aplica)
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auditoria;
use App\Core\Database;
use App\Repositories\FormularioDinamicoRepository;

function lerArgumento($argv, $nome)
{
    foreach ($argv as $arg) {
        if (strpos($arg, "--{$nome}=") === 0) {
            return substr($arg, strlen("--{$nome}="));
        }
    }

    return null;
}

$formularioId = lerArgumento($argv, 'formulario-id');
$confirmar = in_array('--confirmar', $argv, true);

if ($formularioId === null) {
    echo "Uso: php database/reabrir_formulario_edicao.php --formulario-id=11 [--confirmar]\n";
    exit(1);
}

$formularioId = (int) $formularioId;

$formularios = new FormularioDinamicoRepository();
$formulario = $formularios->buscarPorId($formularioId);

if ($formulario === null) {
    echo "Nenhum formulário encontrado com id {$formularioId}.\n";
    exit(1);
}

echo "Formulário: \"{$formulario['nome']}\" (id {$formularioId}, v{$formulario['versao']}, status atual: {$formulario['status']})\n";
echo str_repeat('-', 70) . "\n";

if ($formulario['status'] === 'rascunho') {
    echo "Já está em rascunho — os campos já podem ser editados normalmente pela tela. Nada a fazer.\n";
    exit;
}

$pdo = Database::conexao();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM submissoes WHERE formulario_dinamico_id = :id');
$stmt->execute(['id' => $formularioId]);
$totalSubmissoes = (int) $stmt->fetchColumn();

if ($totalSubmissoes > 0) {
    echo "RECUSADO: este formulário já tem {$totalSubmissoes} submissão(ões) real(is) registrada(s).\n";
    echo "Reabrir pra edição poderia mudar o formulário debaixo de dado já enviado por equipe.\n";
    echo "Use \"Duplicar\" na tela Formulários e edite a nova versão em vez disso.\n";
    exit(1);
}

echo "Nenhuma submissão real usa este formulário — seguro reabrir para edição.\n";

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi alterado.\n";
    echo "Ao confirmar, o status volta para 'rascunho' (mesmo id, sem duplicar).\n";
    echo "Depois: adicione o campo pela tela normal e publique de novo.\n";
    echo "Para aplicar de verdade, repita o comando com --confirmar.\n";
    exit;
}

$formularios->atualizarStatus($formularioId, 'rascunho');

Auditoria::registrar('reabrir_formulario_edicao', 'formularios_dinamicos', $formularioId, ['status' => $formulario['status']], ['status' => 'rascunho'], 'Reabertura de formulário sem submissão real via CLI (database/reabrir_formulario_edicao.php)');

echo "\nFormulário \"{$formulario['nome']}\" (id {$formularioId}) reaberto para edição (status = rascunho).\n";
echo "Agora: Formulários > Campos > Novo campo. Depois de adicionar, publique de novo.\n";
