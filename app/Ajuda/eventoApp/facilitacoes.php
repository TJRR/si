<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Minhas facilitações',
    'resumo' => 'Atividades deste evento em que você foi designado facilitador (instrutor, professor, palestrante).',
    'operacoes' => [
        [
            'nome' => 'Código de presença online',
            'como' => 'Aparece só quando a atividade aceita participação online. Informe esse código verbalmente para quem está participando de forma online confirmar presença no aplicativo.',
        ],
    ],
    'conceitos' => [],
];
