<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Cartões',
    'resumo' => 'Cartões coloridos com resumo curto, que abrem o texto completo ao clique.',
    'operacoes' => [
        ['nome' => 'Eixo temático', 'como' => 'Apontando para um eixo já cadastrado em Trabalhos, o texto completo vem de lá: o texto oficial do edital continua existindo num lugar só.'],
        ['nome' => 'Título e texto completo', 'como' => 'Em branco, valem o nome e a descrição do eixo escolhido. Preenchidos, valem estes.'],
        ['nome' => 'Efeitos', 'como' => 'Passar o mouse, abrir e fechar. Quem prefere menos movimento no sistema operacional não vê animação nenhuma.'],
    ],
    'conceitos' => ['reordenar_arraste'],
];
