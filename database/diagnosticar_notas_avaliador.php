<?php

/**
 * Fase 24 (bug reportado por avaliador): investiga um caso concreto de
 * "avaliei a Equipe N e ela sumiu da minha lista, mas a Equipe seguinte
 * desapareceu em vez dela" - mostra, para um avaliador+etapa, TODAS as
 * submissoes da etapa com o "numero_equipe" calculado do MESMO jeito que a
 * tela cega do avaliador calcula (posicao em listarPorEtapa(), ver
 * AvaliacaoController::submissoes()), cruzado com as notas realmente
 * lancadas por esse avaliador em cada uma - permite comparar o que o
 * avaliador *acha* que aconteceu (pelos numeros que ele viu na tela) com o
 * que esta *de fato* gravado no banco (por submissao_id real, sem sigilo
 * cego - esta e' uma ferramenta do Admin).
 *
 * Também lista, em ordem cronológica, cada nota lançada por esse avaliador
 * nesta etapa (criado_em/atualizado_em) - útil pra reconstruir a sequência
 * real dos salvamentos e comparar com o relato do avaliador.
 *
 * So' leitura, nao altera nada.
 *
 * Uso:
 *   php database/diagnosticar_notas_avaliador.php --email="avaliador@exemplo.com" --etapa_id=42
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Repositories\AvaliadorDesignacaoRepository;
use App\Repositories\CriterioAvaliacaoRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\TrilhaRepository;
use App\Repositories\UsuarioRepository;

function lerArgumento($argv, $nome)
{
    foreach ($argv as $arg) {
        if (strpos($arg, "--{$nome}=") === 0) {
            return substr($arg, strlen("--{$nome}="));
        }
    }

    return null;
}

$email = lerArgumento($argv, 'email');
$etapaId = lerArgumento($argv, 'etapa_id');

if ($email === null || $etapaId === null) {
    echo "Uso: php database/diagnosticar_notas_avaliador.php --email=\"avaliador@exemplo.com\" --etapa_id=42\n";
    exit(1);
}

$etapaId = (int) $etapaId;

$usuarios = new UsuarioRepository();
$usuario = $usuarios->buscarPorEmail($email);

if ($usuario === null) {
    echo "Nenhum usuário encontrado com o e-mail \"{$email}\".\n";
    exit(1);
}

$etapas = new EtapaRepository();
$etapa = $etapas->buscarPorId($etapaId);

if ($etapa === null) {
    echo "Nenhuma etapa encontrada com id {$etapaId}.\n";
    exit(1);
}

$trilha = (new TrilhaRepository())->buscarPorId($etapa['trilha_id']);
$criterios = (new CriterioAvaliacaoRepository())->listarPorEtapa($etapaId);
$totalCriterios = count($criterios);
$designacoes = new AvaliadorDesignacaoRepository();

echo "Avaliador: {$usuario['nome']} <{$usuario['email']}> (usuario_id {$usuario['id']})\n";
echo "Etapa: \"{$etapa['nome']}\" (id {$etapaId}) - Trilha \"{$trilha['nome']}\"\n";
echo "modo_designacao: {$etapa['modo_designacao']} | total de critérios: {$totalCriterios}\n";
echo str_repeat('-', 100) . "\n";

$pdo = Database::conexao();

// Mesma consulta/ordem de SubmissaoRepository::listarPorEtapa() - a base do
// "numero_equipe" que a tela cega do avaliador mostra (posicao + 1, ORDER BY
// s.id ASC), reproduzida aqui pra correlacionar com o relato do avaliador.
$stmt = $pdo->prepare(
    'SELECT s.id AS submissao_id, s.equipe_id, e.nome_equipe
     FROM submissoes s
     LEFT JOIN equipes e ON e.id = s.equipe_id
     WHERE s.etapa_id = :etapa_id
     ORDER BY s.id ASC'
);
$stmt->execute(['etapa_id' => $etapaId]);
$submissoes = $stmt->fetchAll();

if (empty($submissoes)) {
    echo "Nenhuma submissão encontrada nesta etapa.\n";
    exit(0);
}

$designadasIds = $etapa['modo_designacao'] !== 'aberto'
    ? array_map('intval', $designacoes->listarSubmissoesDesignadasNaEtapa($usuario['id'], $etapaId))
    : null;

$stmtNotas = $pdo->prepare(
    'SELECT n.*, c.nome AS criterio_nome
     FROM notas_lancadas n
     INNER JOIN criterios_avaliacao c ON c.id = n.criterio_avaliacao_id
     WHERE n.submissao_id = :submissao_id AND n.usuario_id = :usuario_id
     ORDER BY n.criado_em ASC'
);

echo "\n## Tabela por submissão (numero_equipe = posição na lista cega do avaliador)\n\n";

$todasAsNotasDoAvaliador = [];

foreach ($submissoes as $indice => $submissao) {
    $numeroEquipe = $indice + 1;
    $stmtNotas->execute(['submissao_id' => $submissao['submissao_id'], 'usuario_id' => $usuario['id']]);
    $notasDaSubmissao = $stmtNotas->fetchAll();

    $ehDesignado = $designadasIds === null ? null : in_array((int) $submissao['submissao_id'], $designadasIds, true);
    $nomeEquipe = $submissao['nome_equipe'] !== null ? $submissao['nome_equipe'] : '(sem equipe)';

    $marcador = '';
    if ($ehDesignado === false && !empty($notasDaSubmissao)) {
        $marcador = ' [ATENÇÃO: tem nota deste avaliador MAS NÃO estava designado]';
    }

    echo "Equipe {$numeroEquipe} - \"{$nomeEquipe}\" (submissao_id {$submissao['submissao_id']})";
    echo $ehDesignado === null ? '' : ($ehDesignado ? ' - designado' : ' - NÃO designado');
    echo $marcador . "\n";

    if (empty($notasDaSubmissao)) {
        echo "    (nenhuma nota deste avaliador aqui)\n";
    } else {
        echo '    ' . count($notasDaSubmissao) . "/{$totalCriterios} critério(s) notado(s):\n";

        foreach ($notasDaSubmissao as $nota) {
            echo "      - {$nota['criterio_nome']}: {$nota['nota']} (criado_em {$nota['criado_em']}, atualizado_em {$nota['atualizado_em']})\n";
            $todasAsNotasDoAvaliador[] = [
                'numero_equipe' => $numeroEquipe,
                'nome_equipe' => $nomeEquipe,
                'submissao_id' => $submissao['submissao_id'],
                'criterio_nome' => $nota['criterio_nome'],
                'nota' => $nota['nota'],
                'criado_em' => $nota['criado_em'],
                'atualizado_em' => $nota['atualizado_em'],
            ];
        }
    }

    echo "\n";
}

usort($todasAsNotasDoAvaliador, function ($a, $b) {
    return strcmp($a['criado_em'], $b['criado_em']);
});

echo str_repeat('-', 100) . "\n";
echo "\n## Ordem cronológica de TODAS as notas deste avaliador nesta etapa (criado_em)\n\n";

if (empty($todasAsNotasDoAvaliador)) {
    echo "Nenhuma nota lançada por este avaliador nesta etapa ainda.\n";
} else {
    foreach ($todasAsNotasDoAvaliador as $linha) {
        echo "{$linha['criado_em']} | Equipe {$linha['numero_equipe']} \"{$linha['nome_equipe']}\" (submissao_id {$linha['submissao_id']}) | {$linha['criterio_nome']} = {$linha['nota']} | atualizado_em {$linha['atualizado_em']}\n";
    }
}

echo "\nFim do diagnóstico. Nenhum dado foi alterado.\n";
