<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Meus eventos',
    'resumo' => 'Aparece só quando você está inscrito em mais de um evento; escolha qual deseja abrir.',
    'operacoes' => [
        [
            'nome' => 'Lista de eventos',
            'como' => 'Toque no nome do evento para abrir o painel dele.',
        ],
        [
            'nome' => 'Sino de notificações',
            'como' => 'Mostra avisos de qualquer um dos seus eventos, não só do que você está prestes a escolher aqui.',
        ],
        [
            'nome' => 'Menu (ícone ☰)',
            'como' => 'Abre "Sair", para encerrar sua sessão neste aparelho.',
        ],
    ],
    'conceitos' => [],
];
