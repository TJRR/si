<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Mentorias',
    'resumo' => 'Horários de mentoria 1:1 disponíveis — sua equipe pode ter no máximo 1 reserva ativa por vez.',
    'operacoes' => [
        [
            'nome' => 'Reservar',
            'como' => 'Disponível em qualquer horário vago.',
            'observacao' => 'A reserva pode falhar se outra equipe reservar o mesmo horário no mesmo instante (corrida) — nesse caso, a tela mostra um aviso e o horário some da lista.',
        ],
        [
            'nome' => 'Cancelar',
            'como' => 'Disponível na sua própria reserva, com confirmação — libera o horário para outra equipe.',
        ],
        [
            'nome' => 'Entrar (link do Meet)',
            'como' => 'Só aparece quando o horário tem link disponível (manual ou gerado pela integração com o Google).',
            'observacao' => 'Quando o horário usa integração com Google Agenda, a reconciliação do status acontece sob demanda, ao carregar esta tela — pode levar um instante para o link aparecer.',
        ],
    ],
    'conceitos' => [],
];
