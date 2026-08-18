<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Formulário vinculado (resumo)',
    'resumo' => 'Atalho, dentro da aba da etapa, para nome, versão e status do formulário dinâmico vinculado — sem precisar sair da árvore de navegação para a tela de Formulários.',
    'operacoes' => [
        [
            'nome' => 'Ver formulário público',
            'icone' => 'ver',
            'como' => 'Só aparece quando publicado.',
        ],
        [
            'nome' => 'Campos',
            'como' => 'Abre a lista de campos do formulário.',
        ],
        [
            'nome' => 'Editar',
            'icone' => 'editar',
            'como' => 'Altera nome/descrição do formulário.',
        ],
        [
            'nome' => 'Publicar / Despublicar / Arquivar / Desarquivar',
            'icone' => 'publicar',
            'como' => 'Mesmas ações e mesmo ciclo de vida da tela Formulários — ver o conceito abaixo.',
        ],
    ],
    'conceitos' => ['nunca_apaga_so_versiona'],
];
