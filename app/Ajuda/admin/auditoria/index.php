<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Auditoria',
    'resumo' => 'Registro de todas as ações relevantes do sistema (quem, quando, onde). Só leitura, só Administrador.',
    'operacoes' => [
        [
            'nome' => 'Filtros',
            'como' => 'Busca livre, usuário, ação e período, todos combináveis entre si.',
        ],
        [
            'nome' => 'Ordenação por coluna',
            'como' => 'Clique no cabeçalho da coluna.',
        ],
        [
            'nome' => 'Coluna Ação',
            'como' => 'Categoria colorida pelo tipo de ação registrada.',
            'pills' => [
                ['cor' => 'vermelho', 'rotulo' => 'remover/rejeitar/excluir'],
                ['cor' => 'laranja', 'rotulo' => 'logout/reabrir/falhou'],
                ['cor' => 'verde', 'rotulo' => 'demais ações'],
            ],
        ],
        [
            'nome' => 'Ver detalhes',
            'icone' => 'ver',
            'como' => 'Expande os dados técnicos da alteração (antes/depois) daquele registro.',
        ],
        [
            'nome' => 'Exportar CSV (arquivo de valores separados por vírgula)',
            'como' => 'Baixa a lista filtrada atual.',
        ],
    ],
    'conceitos' => [],
];
