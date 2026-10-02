<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Minha pontuação',
    'resumo' => 'O seu total na gincana do evento, a sua posição na classificação geral e o extrato de onde vieram os seus pontos.',
    'operacoes' => [
        [
            'nome' => 'Total e posição',
            'como' => 'O total soma todos os seus pontos válidos: presença em atividades, competições, conexões, visitas a estandes, divulgação e bônus. A posição é calculada na hora em que você abre a tela. "Com empate" quer dizer que outra pessoa tem exatamente a mesma pontuação e os critérios de desempate não separaram as duas.',
        ],
        [
            'nome' => 'Os primeiros da classificação',
            'como' => 'Aparece quando a organização decidiu mostrar a lista dos primeiros. Conforme a escolha da organização, a lista mostra o nome de cada pessoa ou só a posição e os pontos. A sua linha aparece destacada.',
            'observacao' => 'O nome de quem está abaixo dos primeiros nunca aparece para os outros inscritos.',
        ],
        [
            'nome' => 'Presenças e competições',
            'como' => 'Cada presença pontuada, com o extra de pontualidade quando houver, e cada participação em competição. O que a organização anulou aparece com o motivo.',
        ],
        [
            'nome' => 'Outras origens',
            'como' => 'O detalhe das conexões, dos estandes, da divulgação e dos bônus fica na tela de cada um, pelos atalhos no fim desta tela.',
        ],
        [
            'nome' => 'Encerramento da gincana',
            'como' => 'Quando a organização encerra a gincana, nada mais pontua e a classificação fica congelada: a lista desta tela passa a ser a final.',
        ],
    ],
    'conceitos' => [],
];
