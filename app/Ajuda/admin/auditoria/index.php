<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Auditoria',
    'resumo' => 'Log de todas as ações relevantes do sistema (quem, quando, onde). Só leitura, só Administrador.',
    'operacoes' => [
        [
            'nome' => 'Filtros',
            'como' => 'Busca livre, usuário, ação, período — combináveis.',
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
            'como' => 'Expande o JSON de antes/depois daquele registro.',
        ],
        [
            'nome' => 'Exportar CSV',
            'como' => 'Baixa a lista filtrada atual.',
        ],
    ],
    'conceitos' => [],
];
