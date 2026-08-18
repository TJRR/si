<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Prêmio — novo/editar',
    'resumo' => 'Um item de premiação (geral ou de uma trilha específica).',
    'operacoes' => [
        [
            'nome' => 'Posição',
            'como' => 'Número inteiro ≥1 — define a colocação que o prêmio representa (1º, 2º...).',
        ],
        [
            'nome' => 'Descrição',
            'como' => 'Obrigatória — o que a equipe ganha.',
        ],
        [
            'nome' => 'Imagem/ícone',
            'como' => 'Opcional, 600×600, alt obrigatório se enviada.',
        ],
    ],
    'conceitos' => [],
];
