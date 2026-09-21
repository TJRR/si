<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'FAQ desta edição',
    'resumo' => 'Escolhe quais perguntas do banco global de FAQ ficam ativas na página inicial deste concurso, e em que ordem. Aberto a Administrador e Suporte, e, ao contrário da maioria das telas do núcleo do concurso, aqui o Suporte grava, não só lê. Cada um só age no concurso a que está vinculado.',
    'operacoes' => [
        [
            'nome' => 'Lista "Ativas"',
            'como' => 'Reordenável (ver conceito abaixo); botão ✕ desativa a pergunta desta edição (não apaga do banco global).',
        ],
        [
            'nome' => 'Lista "Disponíveis no banco"',
            'como' => 'Botão "Ativar" liga a pergunta a esta edição.',
            'observacao' => 'Ativar só marca a pergunta como ativa aqui; nunca duplica o texto.',
        ],
        [
            'nome' => '+ Nova pergunta no banco',
            'como' => 'Atalho para cadastrar uma pergunta nova direto no banco global, sem sair desta tela.',
            'observacao' => 'Só aparece para quem tem perfil global: o banco vale para todas as edições, e quem está vinculado a um concurso específico não escreve nele. Esse perfil ativa aqui as perguntas que já existem no banco.',
        ],
        [
            'nome' => 'Perguntas vindas de dúvidas reais',
            'como' => 'A lista "Disponíveis no banco" pode incluir perguntas que nasceram de dúvidas reais de participantes, promovidas na tela de atendimento. Ativar funciona igual para elas.',
        ],
    ],
    'conceitos' => ['banco_global_vs_edicao', 'reordenar_arraste'],
];
