<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'FAQ (banco global)',
    'resumo' => 'Banco global de perguntas frequentes, reaproveitável entre todas as edições do Prêmio — cadastrar aqui não faz a pergunta aparecer em nenhuma home sozinha, é preciso ativá-la em "FAQ desta edição".',
    'operacoes' => [
        [
            'nome' => '+ Nova / Editar / Remover',
            'icone' => 'editar',
            'como' => 'CRUD da pergunta/resposta.',
            'observacao' => 'Remover só funciona se a pergunta não estiver ativa em nenhuma edição no momento.',
        ],
    ],
    'conceitos' => ['banco_global_vs_edicao'],
];
