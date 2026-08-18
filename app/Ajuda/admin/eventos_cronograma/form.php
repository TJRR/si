<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Evento avulso — novo/editar',
    'resumo' => 'Dados de um evento avulso do cronograma.',
    'operacoes' => [
        [
            'nome' => 'Título / Descrição',
            'como' => 'Texto exibido na linha do tempo pública.',
        ],
        [
            'nome' => 'Data de início / fim',
            'como' => 'Início é obrigatório; fim é opcional (evento de um único momento).',
        ],
        [
            'nome' => 'Vínculo com Etapa',
            'como' => 'Opcional — só para referência interna, não altera o comportamento da Etapa vinculada.',
        ],
    ],
    'conceitos' => [],
];
