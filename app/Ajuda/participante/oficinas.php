<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Oficinas',
    'resumo' => 'Encontros coletivos com tema pré-definido — diferente de Mentorias, sua equipe pode se inscrever em quantas oficinas quiser, sem exclusividade.',
    'operacoes' => [
        [
            'nome' => 'Inscrever-se',
            'como' => 'Disponível em qualquer horário.',
        ],
        [
            'nome' => 'Cancelar',
            'como' => 'Disponível na sua inscrição, com confirmação.',
        ],
        [
            'nome' => 'Entrar (link do Meet)',
            'como' => 'Só aparece para quem está inscrito no horário.',
        ],
    ],
    'conceitos' => [],
];
