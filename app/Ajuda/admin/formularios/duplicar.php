<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Duplicar formulário',
    'resumo' => 'Cria uma nova versão editável de um formulário publicado ou despublicado, sem afetar submissões já enviadas com a versão antiga.',
    'operacoes' => [
        [
            'nome' => 'Concurso de destino',
            'como' => 'Escolha para qual concurso a nova versão será criada (pode ser o mesmo ou outro).',
        ],
        [
            'nome' => 'Confirmar duplicação',
            'como' => 'Cria a nova versão (em rascunho) e já leva para a tela de Campos dela.',
        ],
    ],
    'conceitos' => ['nunca_apaga_so_versiona'],
];
