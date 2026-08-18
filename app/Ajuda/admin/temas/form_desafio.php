<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Desafio — novo/editar',
    'resumo' => 'Dados de um Desafio (pergunta) dentro de um Tema.',
    'operacoes' => [
        [
            'nome' => 'Pergunta',
            'como' => 'Textarea obrigatória — é o texto que a equipe vê ao escolher o desafio na inscrição.',
        ],
        [
            'nome' => 'Ícone / Ordem / Ativo',
            'como' => 'Ícone é escolhido de uma lista pré-definida; Ativo controla se o desafio aparece disponível para escolha.',
        ],
    ],
    'conceitos' => [],
];
