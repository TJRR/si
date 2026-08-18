<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Sorteio por categoria — selecionar avaliadores',
    'resumo' => 'Primeiro passo do modo "sorteio por categoria": escolher, dentro de cada categoria, quais avaliadores entram no sorteio.',
    'operacoes' => [
        [
            'nome' => 'Marcar avaliadores por categoria',
            'como' => 'Checkboxes agrupados por categoria de avaliador.',
        ],
        [
            'nome' => 'Continuar',
            'como' => 'Sorteia entre os marcados e leva à tela de prévia de distribuição, para revisar antes de confirmar.',
            'observacao' => 'Exige que "Vagas por categoria" já esteja configurada para esta etapa — sem isso, não há como saber quantos avaliadores sortear de cada categoria.',
        ],
    ],
    'conceitos' => [],
];
