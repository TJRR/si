<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Apresentação de pitch',
    'resumo' => 'Agendamento das apresentações orais desta etapa — cada equipe classificada escolhe data, horário e modalidade (presencial ou online).',
    'operacoes' => [
        [
            'nome' => 'Configuração',
            'como' => 'Janela em que as equipes podem escolher, e-mail institucional que organiza os eventos no Google Agenda, e endereço mostrado a quem escolher apresentação presencial.',
        ],
        [
            'nome' => '+ Novo horário',
            'como' => 'Cria um slot vago (início/fim). O evento no Google só é criado quando uma equipe reserva ou o Admin atribui — a modalidade só é conhecida nesse momento.',
        ],
        [
            'nome' => 'Atribuir manualmente',
            'como' => 'Para equipes classificadas que não escolheram dentro da janela — sempre em modalidade online, conforme o edital.',
        ],
        [
            'nome' => 'Verificar/Tentar novamente',
            'como' => 'Mesmo botão único de Mentoria/Oficina — cobre tanto "o evento nunca chegou a ser criado no Google" quanto "Meet ainda pendente/RSVP desatualizado".',
        ],
        [
            'nome' => 'Presença na sala do Meet',
            'icone' => 'ver',
            'como' => 'Disponível para horário integrado e já encerrado — mesmo relatório de Mentoria/Oficina.',
            'observacao' => 'A Comissão de Avaliação é convidada formalmente do evento (attendee, com RSVP), mas não entra no cruzamento de presença — o sistema só identifica automaticamente integrantes da equipe cadastrados como participantes. Um avaliador que entrar na sala aparece em "Entraram sem identificação".',
        ],
    ],
    'conceitos' => ['presenca_meet'],
];
