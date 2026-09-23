<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Programação',
    'resumo' => 'Programação completa com uma aba por dia, dividida em manhã, tarde e noite.',
    'operacoes' => [
        ['nome' => 'De onde vem a programação', 'como' => 'No modo vinculado, a seção lê as Atividades do evento e agrupa por dia e turno sozinha, usando o tipo cadastrado como etiqueta. No modo digitado, usa os itens abaixo.'],
        ['nome' => 'Turno', 'como' => 'No modo vinculado, sai do horário de início: até 12h é manhã, até 18h é tarde, depois é noite.'],
        ['nome' => 'Mostrar o local', 'como' => 'Desmarcado, a programação fica só com horário, tipo e título.'],
    ],
    'conceitos' => ['reordenar_arraste'],
];
