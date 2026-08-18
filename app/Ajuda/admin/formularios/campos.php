<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Campos do formulário',
    'resumo' => 'Lista de campos de um formulário dinâmico, na ordem em que aparecem para quem preenche.',
    'operacoes' => [
        [
            'nome' => '+ Novo campo',
            'como' => 'Abre o formulário de um campo novo.',
            'observacao' => 'Só disponível enquanto o formulário estiver em status rascunho.',
        ],
        [
            'nome' => 'Editar',
            'icone' => 'editar',
            'como' => 'Altera rótulo, tipo, obrigatoriedade e demais configurações do campo.',
        ],
        [
            'nome' => 'Mover cima/baixo',
            'icone' => 'mover_cima',
            'como' => 'Ajusta a ordem de exibição dos campos.',
        ],
        [
            'nome' => 'Remover',
            'icone' => 'remover',
            'como' => 'Apaga o campo.',
        ],
    ],
    'conceitos' => ['nunca_apaga_so_versiona'],
];
