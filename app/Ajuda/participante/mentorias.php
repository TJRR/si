<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Mentorias',
    'resumo' => 'Mentoria é uma conversa individual da sua equipe com um mentor, num horário marcado. Nesta tela aparecem os horários disponíveis. A equipe pode ter uma reserva ativa por vez: para marcar outra, cancele a atual ou espere ela acontecer.',
    'operacoes' => [
        [
            'nome' => 'Quais horários aparecem',
            'como' => 'Alguns horários são reservados a uma etapa e só aparecem para as equipes classificadas na etapa anterior. Se um horário que você via sumiu, foi porque ele passou a ser só de uma etapa em que a sua equipe não está.',
        ],
        [
            'nome' => 'Reservar',
            'como' => 'Clique em "Reservar" no horário vago que preferir. O horário passa a ser da sua equipe e sai da lista das outras.',
            'observacao' => 'Se outra equipe reservar o mesmo horário no mesmo instante, só uma consegue: a tela avisa e o horário some da lista. Escolha outro.',
        ],
        [
            'nome' => 'Cancelar',
            'como' => 'Na reserva da sua equipe, clique em "Cancelar" e confirme. O horário volta a ficar livre para as outras equipes.',
        ],
        [
            'nome' => 'Entrar na sala',
            'como' => 'No horário marcado, use o botão "Entrar" para abrir a sala da reunião no Google Meet. O botão só aparece quando a sala já existe.',
            'observacao' => 'Quando a sala é criada automaticamente pela agenda do Google, ela pode levar um instante para aparecer: abra esta tela de novo daqui a pouco.',
        ],
    ],
    'conceitos' => [],
];
