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
        ['nome' => 'Faixa de horário do turno', 'como' => 'Cada dia mostra uma coluna por turno. O cabeçalho da coluna traz o horário do item mais cedo ao mais tarde daquele turno, montado sozinho a partir dos horários digitados ("08h30 às 10h", "10h30 às 12h30" viram "Manhã · 08h30 às 12h30"). Quando todos os itens do turno têm o mesmo horário, ele aparece só no cabeçalho, sem se repetir em cada cartão.'],
    ],
    'conceitos' => ['reordenar_arraste'],
];
