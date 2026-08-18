<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Formulário — novo/editar',
    'resumo' => 'Nome e descrição de um formulário dinâmico.',
    'operacoes' => [
        [
            'nome' => 'Nome / Descrição',
            'como' => 'Ao criar um formulário novo, Salvar já leva direto para a tela de Campos.',
        ],
    ],
    'conceitos' => [],
];
