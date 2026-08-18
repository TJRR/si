<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Temas',
    'resumo' => 'Grandes áreas de desafio de uma trilha (do edital), exibidas na home pública — cada tema agrupa vários Desafios.',
    'operacoes' => [
        [
            'nome' => '+ Novo tema',
            'como' => 'Abre o formulário de um novo tema.',
        ],
        [
            'nome' => 'Ver desafios',
            'como' => 'Abre a lista de desafios (perguntas) deste tema.',
        ],
        [
            'nome' => 'Editar',
            'icone' => 'editar',
            'como' => 'Altera nome, descrição, ícone e ordem.',
        ],
        [
            'nome' => 'Remover',
            'icone' => 'remover',
            'como' => 'Apaga o tema, com confirmação. Só Administrador.',
            'observacao' => 'Só funciona se o tema ainda não tiver desafios cadastrados.',
        ],
        [
            'nome' => 'Reordenar',
            'como' => 'Ver conceito "Reordenar por arraste" abaixo — aqui a ordem define a posição dos temas na home pública.',
        ],
    ],
    'conceitos' => ['reordenar_arraste', 'permissao_suporte_admin'],
];
