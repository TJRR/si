<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Mensagens recebidas (Contato)',
    'resumo' => 'Mensagens enviadas pelo formulário nativo de contato da home, quando ativado. Tela só de leitura.',
    'operacoes' => [
        [
            'nome' => 'Responder',
            'como' => 'Use o link mailto: da própria mensagem — a resposta acontece fora do sistema, no seu cliente de e-mail.',
        ],
    ],
    'conceitos' => [],
];
