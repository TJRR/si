<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Páginas (conteúdo legado)',
    'resumo' => 'Textos e imagens avulsas cadastradas como pares de nome e valor, anterior à criação dos Blocos de Conteúdo estruturados. Mantida só por compatibilidade; não é o caminho recomendado para conteúdo novo.',
    'operacoes' => [
        [
            'nome' => 'Campos exibidos',
            'como' => 'Variam conforme o que já existe cadastrado no banco: a tela é dirigida pelos dados já existentes, não por uma lista fixa predefinida.',
        ],
    ],
    'conceitos' => [],
];
