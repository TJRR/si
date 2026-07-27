<?php

/**
 * Fase 21: lista todas as submissoes de uma equipe (nao so' a mais recente -
 * SubmissaoRepository::buscarPorEquipe() usa LIMIT 1, insuficiente para
 * detectar duplicata). Uso pontual: equipe enviou submissao mais de uma vez
 * na mesma etapa (a tabela `submissoes` nao tem constraint UNIQUE em
 * equipe_id+etapa_id, entao nada no banco impede isso - o fluxo web atual ja
 * evita duplicar em reenvio normal, mas dado historico pode ter ficado
 * duplicado).
 *
 * Mostra, para cada submissao encontrada: id, etapa, criado_em/atualizado_em,
 * carimbo_data_hora (se veio de importacao Google Forms) e quantas linhas
 * dependentes existem em cada tabela que referencia submissoes.id - use isso
 * para decidir quais ids remover com database/remover_submissoes.php.
 *
 * So' leitura, nao precisa de --confirmar.
 *
 * Uso:
 *   php diagnosticar_submissoes_equipe.php --equipe="KIP Tecnologia"
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Repositories\EquipeRepository;

function lerArgumento($argv, $nome)
{
    foreach ($argv as $arg) {
        if (strpos($arg, "--{$nome}=") === 0) {
            return substr($arg, strlen("--{$nome}="));
        }
    }

    return null;
}

$nomeEquipe = lerArgumento($argv, 'equipe');

if ($nomeEquipe === null || $nomeEquipe === '') {
    echo "Uso: php diagnosticar_submissoes_equipe.php --equipe=\"KIP Tecnologia\"\n";
    exit(1);
}

$equipes = new EquipeRepository();
$equipe = $equipes->buscarPorNome($nomeEquipe);

if ($equipe === null) {
    $semelhantes = $equipes->listarSemelhantesPorNome($nomeEquipe);

    if (empty($semelhantes)) {
        echo "Nenhuma equipe encontrada com o nome \"{$nomeEquipe}\".\n";
        exit(1);
    }

    echo "Nenhuma equipe com nome EXATO \"{$nomeEquipe}\" - equipes parecidas encontradas:\n";
    foreach ($semelhantes as $item) {
        echo "  - \"{$item['nome_equipe']}\" (id {$item['id']})\n";
    }
    echo "\nRode de novo com o nome exato de uma das opções acima.\n";
    exit(1);
}

echo "Equipe: \"{$equipe['nome_equipe']}\" (id {$equipe['id']}, trilha_id {$equipe['trilha_id']})\n\n";

$pdo = Database::conexao();
$stmt = $pdo->prepare(
    'SELECT s.id, s.etapa_id, e.nome AS etapa_nome, s.criado_em, s.atualizado_em, s.dados_json
     FROM submissoes s
     INNER JOIN etapas e ON e.id = s.etapa_id
     WHERE s.equipe_id = :equipe_id
     ORDER BY s.etapa_id ASC, s.id ASC'
);
$stmt->execute(['equipe_id' => $equipe['id']]);
$submissoes = $stmt->fetchAll();

if (empty($submissoes)) {
    echo "Nenhuma submissão encontrada para esta equipe.\n";
    exit(0);
}

$tabelasDependentes = [
    'submissao_cpfs' => 'submissao_id',
    'avaliador_designacoes' => 'submissao_id',
    'feedback_submissao' => 'submissao_id',
    'notas_lancadas' => 'submissao_id',
    'resultados_etapa' => 'submissao_id',
];

echo count($submissoes) . " submissão(ões) encontrada(s):\n\n";

foreach ($submissoes as $submissao) {
    $dados = json_decode((string) $submissao['dados_json'], true);
    $carimbo = isset($dados['carimbo_data_hora']) ? $dados['carimbo_data_hora'] : '(nao importado do Google Forms)';

    echo "id {$submissao['id']} - etapa \"{$submissao['etapa_nome']}\" (etapa_id {$submissao['etapa_id']})\n";
    echo "  criado_em: {$submissao['criado_em']} | atualizado_em: {$submissao['atualizado_em']}\n";
    echo "  carimbo_data_hora (Google Forms): {$carimbo}\n";

    foreach ($tabelasDependentes as $tabela => $coluna) {
        $stmtDep = $pdo->prepare("SELECT COUNT(*) FROM `{$tabela}` WHERE `{$coluna}` = :submissao_id");
        $stmtDep->execute(['submissao_id' => $submissao['id']]);
        $total = (int) $stmtDep->fetchColumn();

        if ($total > 0) {
            echo "  [ATENCAO] {$total} linha(s) em {$tabela}\n";
        }
    }

    echo "\n";
}
