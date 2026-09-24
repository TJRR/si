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
        ['nome' => 'Cores do cartão', 'como' => 'A cor da etiqueta colore o texto pequeno acima do título ("EIXO 1") e o "Saiba mais"; a cor de fundo do cartão é o tom claro de fundo dele.'],
        ['nome' => 'Texto de apoio da seção', 'como' => 'Aparece acima dos cartões, com a etiqueta e o título da seção. Na página da 5ª Semana, é ele que faz a apresentação "Sobre o evento".'],
    ],
    'conceitos' => ['reordenar_arraste'],
];
