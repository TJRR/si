<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Meus estandes',
    'resumo' => 'Estandes que você representa, um por evento. Escolha um para ver as visitas, atualizar os dados ou imprimir o cartaz.',
    'operacoes' => [
        ['nome' => 'Abrir estande', 'como' => 'Abre o painel do estande daquele evento.'],
    ],
    'conceitos' => [],
];
