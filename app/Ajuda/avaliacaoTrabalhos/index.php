<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Trabalhos para avaliar',
    'resumo' => 'Lista dos trabalhos que foram designados a você para avaliação, em qualquer evento em que você é avaliador avulso.',
    'operacoes' => [
        [
            'nome' => 'Identificação',
            'como' => 'Mostra o número do trabalho quando a avaliação deste evento é às cegas, ou o nome do autor principal quando não é.',
        ],
        [
            'nome' => 'Situação da avaliação',
            'como' => '"Pendente" enquanto faltar nota de algum critério; "Concluída" quando todos os critérios já tiverem nota lançada.',
        ],
        [
            'nome' => 'Avaliar',
            'como' => 'Abre a ficha do trabalho com o conteúdo e o formulário de notas.',
        ],
    ],
    'conceitos' => ['sigilo_anonimato'],
];
