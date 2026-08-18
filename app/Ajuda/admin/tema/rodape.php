<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Tema — Rodapé',
    'resumo' => 'Aparência e conteúdo do rodapé da home.',
    'operacoes' => [
        [
            'nome' => 'Logo alternativa do rodapé',
            'como' => 'Opcional — se vazia, usa a logo padrão.',
        ],
        [
            'nome' => 'Mostrar Trilhas/Cronograma/Desafios/Contato',
            'como' => 'Checkboxes independentes — cada seção pode aparecer ou não no rodapé, sem afetar sua exibição no corpo da página.',
            'observacao' => '"Sobre"/"Premiação"/blocos livres têm sua própria opção de rodapé configurada em Blocos de Conteúdo, não nesta tela.',
        ],
    ],
    'conceitos' => [],
];
