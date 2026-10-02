<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Oficinas',
    'resumo' => 'Oficinas são encontros coletivos sobre um tema definido pela organização, abertos a várias equipes ao mesmo tempo. Diferente das mentorias, a sua equipe pode se inscrever em quantas oficinas quiser.',
    'operacoes' => [
        [
            'nome' => 'Inscrever-se',
            'como' => 'Clique em "Inscrever-se" no encontro que interessar. Alguns encontros são reservados a uma etapa e só aparecem para as equipes classificadas na etapa anterior; se um encontro sumiu da lista, foi por isso.',
        ],
        [
            'nome' => 'Cancelar',
            'como' => 'Na sua inscrição, clique em "Cancelar" e confirme.',
        ],
        [
            'nome' => 'Entrar na sala',
            'como' => 'No horário do encontro, use o botão "Entrar" para abrir a sala no Google Meet. Ele só aparece para quem está inscrito.',
        ],
    ],
    'conceitos' => [],
];
