<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Requerimentos',
    'resumo' => 'Pedidos formais com assinatura digital — diferente de Dúvidas, só o líder da equipe tem acesso a esta tela.',
    'operacoes' => [
        [
            'nome' => 'Iniciar',
            'como' => 'Por modelo disponível.',
            'observacao' => 'Um modelo só aparece se a etapa vinculada a ele já estiver liberada para a sua equipe. Se já existir um pedido em andamento daquele modelo, "Iniciar" leva direto para ele — não é possível abrir dois pedidos do mesmo modelo ao mesmo tempo.',
        ],
        [
            'nome' => 'Ver',
            'icone' => 'ver',
            'como' => 'Abre o detalhe de um pedido existente.',
        ],
    ],
    'conceitos' => [],
];
