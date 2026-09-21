<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Cronograma: eventos avulsos',
    'resumo' => 'Eventos extras da linha do tempo pública, que não são Etapas formais (ex.: uma oficina, uma cerimônia de abertura). Aparecem misturados às Etapas na linha do tempo, ordenados por data. Não há reordenação manual aqui.',
    'operacoes' => [
        [
            'nome' => '+ Novo',
            'como' => 'Abre o formulário de um evento novo.',
        ],
        [
            'nome' => 'Editar / Remover',
            'icone' => 'editar',
            'como' => 'Criar, editar e remover o evento.',
        ],
    ],
    'conceitos' => [],
];
