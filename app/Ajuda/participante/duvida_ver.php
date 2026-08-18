<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Ver dúvida',
    'resumo' => 'Thread completa da dúvida, com avatar de quem escreveu cada mensagem.',
    'operacoes' => [
        [
            'nome' => 'Reabrir dúvida',
            'como' => 'Textarea obrigatória + anexo opcional, para continuar a conversa depois de uma resposta.',
            'observacao' => 'Só aparece quando a dúvida já está com status "Respondida" — não é possível reabrir uma dúvida ainda em análise.',
        ],
    ],
    'conceitos' => [],
];
