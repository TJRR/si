<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Resultado da Etapa',
    'resumo' => 'Prévia (ou resultado publicado) do ranking desta etapa, calculado a partir das notas lançadas e da Fórmula da Etapa. Se a fórmula ou os critérios estiverem incompletos, a tela mostra um erro no lugar do ranking.',
    'operacoes' => [
        [
            'nome' => 'Confirmar e publicar',
            'icone' => 'publicar',
            'como' => 'Publica o ranking desta etapa numa página pública.',
            'observacao' => 'Publicar bloqueia novos lançamentos de nota nesta etapa — nenhum avaliador consegue mais salvar notas depois disso.',
        ],
        [
            'nome' => 'Reabrir',
            'icone' => 'despublicar',
            'como' => 'Apaga o resultado publicado e libera o lançamento de notas de novo.',
        ],
        [
            'nome' => 'Ver submissão / Ver avaliações',
            'icone' => 'ver',
            'como' => 'Abrem em popup o conteúdo enviado pela equipe, ou as notas e feedbacks de cada avaliador daquela submissão.',
        ],
        [
            'nome' => 'Gerar relatório de auditoria (PDF)',
            'como' => 'Relatório com heatmap, ranking e integrantes homologados, com avaliadores anonimizados por letras locais a cada submissão (não é possível seguir o mesmo avaliador entre submissões diferentes).',
        ],
        [
            'nome' => 'Gerar relatório de notas (PDF)',
            'como' => 'Relatório de uso interno, com as iniciais reais do avaliador na etapa (não anonimizado) — não deve circular fora da administração.',
        ],
    ],
    'conceitos' => ['publicar_trava', 'sigilo_anonimato'],
];
