<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Seção Estandes',
    'resumo' => 'Mostra na página pública do evento os estandes ativos, na ordem cadastrada em Estandes, com logotipo, categoria, nome e descrição. O código de visita nunca aparece na página.',
    'operacoes' => [
        ['nome' => 'Etiqueta, título e texto de apoio', 'como' => 'Cabeçalho da seção na página.'],
        ['nome' => 'Cores', 'como' => 'Cor de fundo e cor do texto da seção, em formato #rrggbb.'],
        ['nome' => 'Ir para Estandes', 'como' => 'Abre o cadastro dos estandes, onde ficam os dados de cada um.'],
    ],
    'conceitos' => [],
];
