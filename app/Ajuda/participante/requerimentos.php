<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Requerimentos',
    'resumo' => 'Requerimentos são pedidos formais à organização, com documento assinado digitalmente. Só o líder da equipe tem acesso a esta tela; para perguntas do dia a dia, use Dúvidas, que vale para todos os integrantes.',
    'operacoes' => [
        [
            'nome' => 'Iniciar',
            'como' => 'Escolha o modelo de requerimento e clique em "Iniciar" para abrir o pedido.',
            'observacao' => 'Um modelo só aparece quando a etapa ligada a ele já está aberta para a sua equipe. Se já existir um pedido em andamento do mesmo modelo, "Iniciar" leva direto a ele: não é possível ter dois pedidos do mesmo modelo ao mesmo tempo.',
        ],
        [
            'nome' => 'Ver',
            'icone' => 'ver',
            'como' => 'Abre um pedido já iniciado, com a situação dele e os próximos passos.',
        ],
    ],
    'conceitos' => [],
];
