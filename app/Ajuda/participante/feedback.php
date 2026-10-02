<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Notas e comentário',
    'resumo' => 'Aqui você vê como a sua equipe foi avaliada numa etapa: a nota de cada critério dada por cada avaliador, a média, a Nota Final da etapa e, quando a etapa prevê, o comentário dos avaliadores. Os avaliadores aparecem sempre como "Avaliador 1", "Avaliador 2" e assim por diante, nunca pelo nome.',
    'operacoes' => [
        [
            'nome' => 'Quando a tela fica disponível',
            'como' => 'Só depois que a organização publica o resultado da etapa. Antes disso, o botão "Ver notas e comentário" não aparece na tela "Minha inscrição".',
        ],
        [
            'nome' => 'Como ler a Nota Final',
            'como' => 'A Nota Final mostrada aqui é exatamente a que saiu da fórmula oficial da etapa, a mesma usada na classificação. O comentário pode vir por critério ou para o trabalho inteiro, conforme a etapa foi configurada.',
        ],
    ],
    'conceitos' => ['sigilo_anonimato'],
];
