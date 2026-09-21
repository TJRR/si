<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Aparência',
    'resumo' => 'Escolha do tema de cores da sua conta, disponível para qualquer usuário autenticado.',
    'operacoes' => [
        [
            'nome' => 'Escolher um tema',
            'como' => 'A cor escolhida vale para todo o sistema (portal público, painel administrativo e aplicativo de Evento), só para você.',
            'observacao' => 'Quem não escolher nenhum tema vê o tema marcado como padrão pelo Administrador.',
        ],
    ],
    'conceitos' => [],
];
