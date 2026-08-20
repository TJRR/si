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
            'como' => 'Disponível nos horários que aparecem para a sua equipe. Alguns encontros são vinculados a uma etapa: nesse caso só aparecem para quem está habilitado a ela, ou seja, foi classificado na etapa anterior. Se um encontro que você via sumiu da lista, é porque ele passou a ser restrito a uma etapa em que a sua equipe não está habilitada.',
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
