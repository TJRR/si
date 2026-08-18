<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Reordenar por arraste',
    'texto' => 'Nas listas com esse recurso, arraste um item pela alça "⠿" para a posição desejada — a nova ordem é salva sozinha, sem precisar de um botão "Salvar" separado. Sem mouse ou touch, use as setas ▲▼ ao lado de cada item (acessibilidade). A ordem aqui é sempre de exibição — o que muda em cada tela específica é o que essa ordem afeta (ex.: ordem dos cartões na home, prioridade de uma regra de desempate).',
];
