<?php

/**
 * Fase 17 (Bug 1): substitui os campos placeholder da Etapa 1 (Submissao de
 * Ideia) pelos campos reais dos formularios do Google (Etapa1TrilhaInterna.pdf
 * / Etapa1TrilhaExterna.pdf) - hoje o formulario tem CPF do Responsavel, Nome
 * do Projeto, E-mail, Documento PDF e Participantes, nenhum dos quais existe
 * no edital real; e falta Solucao Proposta, Potencial de Impacto, Viabilidade
 * da Solucao (e Premissas de Tecnologia, exclusivo da Externa).
 *
 * A etapa e' localizada por ordem=2 (estavel nas duas trilhas), nao por nome
 * (o nome diverge entre trilhas - ver EtapaRepository::buscarPorTrilhaEOrdem()).
 *
 * Por padrao roda em modo consulta (dry-run): so mostra o que seria feito,
 * sem gravar nada. Para gravar de verdade:
 *   php reconstruir_campos_etapa1.php --confirmar
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Repositories\CampoDinamicoRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\TrilhaRepository;

const ORDEM_ETAPA_SUBMISSAO = 2;

// campos_dinamicos.rotulo e' VARCHAR(150) - o texto integral do edital
// (155 caracteres) estourava essa coluna e derrubava o script no meio
// (achado real ao rodar em dev, 16/07/2026). Versao encurtada, preservando
// o sentido, dentro do limite.
$potencialImpactoPadrao = 'Potencial de Impacto';
$potencialImpactoExterna = 'Potencial de Impacto (considerando o grau de importância da mudança, a '
    . 'amplitude dos resultados esperados e a otimização de tempo e recursos)';

$camposPorTrilha = [
    'Trilha Interna' => [
        ['rotulo' => 'Desafio Escolhido', 'tipo' => 'selecao_tema_desafio', 'obrigatorio' => 1],
        ['rotulo' => 'Solução Proposta', 'tipo' => 'texto_longo', 'obrigatorio' => 1],
        ['rotulo' => $potencialImpactoPadrao, 'tipo' => 'texto_longo', 'obrigatorio' => 1],
        ['rotulo' => 'Viabilidade da Solução', 'tipo' => 'texto_longo', 'obrigatorio' => 1],
    ],
    'Trilha Externa' => [
        ['rotulo' => 'Desafio Escolhido', 'tipo' => 'selecao_tema_desafio', 'obrigatorio' => 1],
        ['rotulo' => 'Solução Proposta', 'tipo' => 'texto_longo', 'obrigatorio' => 1],
        ['rotulo' => $potencialImpactoExterna, 'tipo' => 'texto_longo', 'obrigatorio' => 1],
        ['rotulo' => 'Viabilidade da Solução', 'tipo' => 'texto_longo', 'obrigatorio' => 1],
        ['rotulo' => 'Premissas de Tecnologia', 'tipo' => 'texto_longo', 'obrigatorio' => 1],
    ],
];

$confirmar = in_array('--confirmar', $argv, true);

// Validacao defensiva: nunca mais deixar um rotulo estourar a coluna em
// silencio - falha antes de remover qualquer campo existente.
foreach ($camposPorTrilha as $nomeTrilha => $camposNovos) {
    foreach ($camposNovos as $campo) {
        if (mb_strlen($campo['rotulo']) > 150) {
            echo "ERRO: rotulo '{$campo['rotulo']}' tem " . mb_strlen($campo['rotulo']) . " caracteres - excede o limite de 150 da coluna 'rotulo'. Abortando sem gravar nada.\n";
            exit(1);
        }
    }
}

$trilhas = new TrilhaRepository();
$etapas = new EtapaRepository();
$campos = new CampoDinamicoRepository();
$pdo = Database::conexao();

foreach ($camposPorTrilha as $nomeTrilha => $camposNovos) {
    echo "\n=== {$nomeTrilha} ===\n";

    $trilha = $trilhas->buscarPorNome($nomeTrilha);

    if ($trilha === null) {
        echo "ERRO: trilha '{$nomeTrilha}' nao encontrada.\n";
        continue;
    }

    $etapa = $etapas->buscarPorTrilhaEOrdem($trilha['id'], ORDEM_ETAPA_SUBMISSAO);

    if ($etapa === null) {
        echo "ERRO: nenhuma etapa com ordem=" . ORDEM_ETAPA_SUBMISSAO . " encontrada na trilha '{$nomeTrilha}'.\n";
        continue;
    }

    if ($etapa['formulario_dinamico_id'] === null) {
        echo "ERRO: etapa '{$etapa['nome']}' nao tem formulario vinculado.\n";
        continue;
    }

    $formularioId = $etapa['formulario_dinamico_id'];
    $camposAtuais = $campos->listarPorFormulario($formularioId);

    echo "Etapa: '{$etapa['nome']}' (formulario #{$formularioId})\n";
    echo "Campos atuais (" . count($camposAtuais) . "):\n";
    foreach ($camposAtuais as $campo) {
        echo "  - [{$campo['tipo']}] {$campo['rotulo']}\n";
    }

    echo "Campos novos (" . count($camposNovos) . "):\n";
    foreach ($camposNovos as $campo) {
        echo "  + [{$campo['tipo']}] {$campo['rotulo']}\n";
    }

    if (!$confirmar) {
        echo "[simulado] " . count($camposAtuais) . " campo(s) seriam removidos e " . count($camposNovos) . " criados.\n";
        continue;
    }

    $pdo->beginTransaction();

    try {
        foreach ($camposAtuais as $campo) {
            $campos->remover($campo['id']);
        }

        foreach ($camposNovos as $campo) {
            $campos->criar($formularioId, $campo['rotulo'], $campo['tipo'], $campo['obrigatorio'], []);
        }

        $pdo->commit();
        echo "[ok] campos substituidos.\n";
    } catch (\Exception $e) {
        $pdo->rollBack();
        echo "[ERRO] '{$nomeTrilha}': " . $e->getMessage() . " - nada foi alterado nesta trilha.\n";
    }
}

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi gravado.\n";
    echo "Para gravar de verdade, rode: php reconstruir_campos_etapa1.php --confirmar\n";
}
