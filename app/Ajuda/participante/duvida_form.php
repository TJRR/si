<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Registrar dúvida',
    'resumo' => 'Formulário de registro de uma nova dúvida.',
    'operacoes' => [
        [
            'nome' => 'Pergunta + anexo',
            'como' => 'Textarea obrigatória, anexo opcional (PDF ou imagem) → "Registrar".',
            'observacao' => 'Notifica todos os administradores do concurso da trilha da sua equipe.',
        ],
    ],
    'conceitos' => [],
];
