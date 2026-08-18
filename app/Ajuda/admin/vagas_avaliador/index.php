<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Vagas por categoria',
    'resumo' => 'Define quantos avaliadores de cada categoria entram no sorteio desta etapa, quando o modo de designação é "sorteio por categoria".',
    'operacoes' => [
        [
            'nome' => 'Quantidade por categoria',
            'como' => 'Preencha um número para cada categoria e clique em Salvar.',
            'observacao' => 'Categoria com 0 ou em branco não entra no sorteio. A tela avisa e desabilita a edição se a etapa não estiver em modo "sorteio por categoria", ou se ainda não houver categorias cadastradas.',
        ],
    ],
    'conceitos' => [],
];
