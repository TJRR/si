<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'FAQ desta edição',
    'resumo' => 'Escolhe quais perguntas do banco global de FAQ ficam ativas na home deste concurso, e em que ordem.',
    'operacoes' => [
        [
            'nome' => 'Lista "Ativas"',
            'como' => 'Reordenável (ver conceito abaixo); botão ✕ desativa a pergunta desta edição (não apaga do banco global).',
        ],
        [
            'nome' => 'Lista "Disponíveis no banco"',
            'como' => 'Botão "Ativar" liga a pergunta a esta edição.',
            'observacao' => 'Ativar só marca a pergunta como ativa aqui — nunca duplica o texto.',
        ],
        [
            'nome' => '+ Nova pergunta no banco',
            'como' => 'Atalho para cadastrar uma pergunta nova direto no banco global, sem sair desta tela.',
        ],
    ],
    'conceitos' => ['banco_global_vs_edicao', 'reordenar_arraste'],
];
