<?php

/**
 * Fase 17 (Melhoria 3): prepara a Etapa 2 (submissao de video) nas duas
 * trilhas - cria o formulario dinamico (link do YouTube + descricao/resumo
 * do video, confirmado com o usuario) e vincula a etapa correspondente
 * (ordem=3, ver EtapaRepository::buscarPorTrilhaEOrdem).
 *
 * A infraestrutura de link_youtube ja existe ponta a ponta desde a Fase 13
 * (validacao, iframe no avaliador, pagina publica de resultado) - este
 * script so' cadastra o formulario/campos, nao muda nenhum codigo.
 *
 * NAO cadastra criterios de avaliacao da Etapa 2 - nao ha edital/pesos reais
 * disponiveis para esta etapa ainda; isso fica para quando o Admin tiver essa
 * informacao (tela Criterios da propria Etapa 2).
 *
 * Por padrao roda em modo consulta (dry-run): so mostra o que seria feito,
 * sem gravar nada. Para gravar de verdade:
 *   php configurar_etapa2_video.php --confirmar
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Repositories\CampoDinamicoRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\FormularioDinamicoRepository;
use App\Repositories\TrilhaRepository;

const ORDEM_ETAPA_VIDEO = 3;

$confirmar = in_array('--confirmar', $argv, true);

$trilhas = new TrilhaRepository();
$etapas = new EtapaRepository();
$formularios = new FormularioDinamicoRepository();
$campos = new CampoDinamicoRepository();

foreach (['Trilha Interna', 'Trilha Externa'] as $nomeTrilha) {
    echo "\n=== {$nomeTrilha} ===\n";

    $trilha = $trilhas->buscarPorNome($nomeTrilha);

    if ($trilha === null) {
        echo "ERRO: trilha '{$nomeTrilha}' nao encontrada.\n";
        continue;
    }

    $etapa = $etapas->buscarPorTrilhaEOrdem($trilha['id'], ORDEM_ETAPA_VIDEO);

    if ($etapa === null) {
        echo "ERRO: nenhuma etapa com ordem=" . ORDEM_ETAPA_VIDEO . " encontrada na trilha '{$nomeTrilha}'.\n";
        continue;
    }

    if ($etapa['formulario_dinamico_id'] !== null) {
        echo "AVISO: etapa '{$etapa['nome']}' já tem formulário vinculado (#{$etapa['formulario_dinamico_id']}) - nada a fazer.\n";
        continue;
    }

    echo "Etapa: '{$etapa['nome']}'\n";
    echo "Seria criado: formulário 'Submissão de Vídeo - {$nomeTrilha}' com 2 campos:\n";
    echo "  - [link_youtube] Link do Vídeo (YouTube)\n";
    echo "  - [texto_longo] Descrição/Resumo do Vídeo\n";

    if (!$confirmar) {
        echo "[simulado]\n";
        continue;
    }

    $formularioId = $formularios->criar(
        $trilha['concurso_id'],
        'Submissão de Vídeo - ' . $nomeTrilha,
        '',
        1,
        'publicado'
    );

    $campos->criar($formularioId, 'Link do Vídeo (YouTube)', 'link_youtube', 1, []);
    $campos->criar($formularioId, 'Descrição/Resumo do Vídeo', 'texto_longo', 1, []);

    $etapas->atualizar(
        $etapa['id'],
        $etapa['nome'],
        (string) $etapa['descricao'],
        $etapa['ordem'],
        (string) $etapa['data_inicio'],
        (string) $etapa['data_fim'],
        $formularioId,
        (string) $etapa['regra_transicao_tipo'],
        (string) $etapa['regra_transicao_valor'],
        [
            'modo_designacao' => $etapa['modo_designacao'],
            'qtd_avaliadores_por_submissao' => $etapa['qtd_avaliadores_por_submissao'],
            'modo_consolidacao' => $etapa['modo_consolidacao'],
            'modo_sigilo' => $etapa['modo_sigilo'],
            'modo_avanco' => $etapa['modo_avanco'],
            'mecanismo_avaliacao' => $etapa['mecanismo_avaliacao'],
            'modo_feedback_avaliador' => $etapa['modo_feedback_avaliador'],
        ]
    );

    echo "[ok] formulário #{$formularioId} criado e vinculado à etapa '{$etapa['nome']}'.\n";
}

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi gravado.\n";
    echo "Para gravar de verdade, rode: php configurar_etapa2_video.php --confirmar\n";
}

echo "\nATENÇÃO: os critérios de avaliação da Etapa 2 NÃO foram cadastrados - não há\n";
echo "edital/pesos reais disponíveis ainda. Cadastre em Admin > Etapa > Critérios\n";
echo "assim que essa informação existir.\n";
