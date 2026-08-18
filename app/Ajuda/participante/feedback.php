<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Notas e Feedback',
    'resumo' => 'Notas por critério e por avaliador (sempre anonimizado — "Avaliador 1", "Avaliador 2"...), média, Nota Final da etapa, e o feedback qualitativo, quando configurado. Só fica disponível depois que o resultado da etapa é publicado. O formato do feedback (por critério ou por submissão inteira) varia conforme a configuração da etapa. A Nota Final mostrada aqui nunca é recalculada à parte — é sempre a mesma que saiu da fórmula oficial da etapa.',
    'operacoes' => [],
    'conceitos' => ['sigilo_anonimato'],
];
