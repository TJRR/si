<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Editar usuário',
    'resumo' => 'Altera nome, perfil, concurso e (se avaliador) categoria de um usuário já cadastrado.',
    'operacoes' => [
        [
            'nome' => 'Perfil / Concurso / Categoria',
            'como' => 'Categoria só aparece quando o perfil escolhido é avaliador.',
            'observacao' => 'E-mail não é editável por aqui — é a identidade da conta.',
        ],
    ],
    'conceitos' => [],
];
