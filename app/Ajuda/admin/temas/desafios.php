<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Desafios (de um tema)',
    'resumo' => 'Perguntas específicas dentro de um Tema — é o que a equipe efetivamente escolhe ao se inscrever.',
    'operacoes' => [
        [
            'nome' => '+ Novo desafio',
            'como' => 'Abre o formulário de um novo desafio.',
        ],
        [
            'nome' => 'Editar',
            'icone' => 'editar',
            'como' => 'Altera pergunta, ícone, ordem e se está ativo.',
        ],
        [
            'nome' => 'Remover',
            'icone' => 'remover',
            'como' => 'Apaga o desafio, com confirmação. Só Administrador.',
            'observacao' => 'Só funciona se o desafio ainda não tiver equipes vinculadas.',
        ],
        [
            'nome' => 'Reordenar',
            'como' => 'Ver conceito "Reordenar por arraste" abaixo.',
        ],
    ],
    'conceitos' => ['reordenar_arraste'],
];
