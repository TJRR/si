<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Apresentação de pitch',
    'resumo' => 'Nesta tela a sua equipe escolhe quando vai fazer a apresentação oral do projeto, o pitch: data, horário e modalidade, presencial ou pela internet. A escolha só pode ser feita dentro do período aberto pela organização.',
    'operacoes' => [
        [
            'nome' => 'Quem pode escolher',
            'como' => 'Qualquer integrante homologado da equipe, e não só o líder. A escolha vale para a equipe inteira.',
        ],
        [
            'nome' => 'Escolher o horário',
            'como' => '1. Escolha a modalidade no horário que preferir. 2. Confirme pelo ícone ao lado. O horário passa a ser da sua equipe.',
            'observacao' => 'Se outra equipe confirmar o mesmo horário no mesmo instante, só uma consegue: a tela avisa e você escolhe outro. Depois de escolhido, o horário não muda pelo sistema; se precisar mudar, procure o ' . nomeUnidadeResponsavel() . '.',
        ],
        [
            'nome' => 'Se o período acabar sem escolha',
            'como' => 'A organização define um horário para a equipe, sempre na modalidade pela internet.',
        ],
        [
            'nome' => 'Entrar na sala',
            'como' => 'Na modalidade pela internet, o botão para entrar na sala do Google Meet aparece quando a sala já foi criada.',
            'observacao' => 'Se o horário foi escolhido há pouco, a sala ainda pode estar sendo criada: abra esta tela de novo daqui a alguns instantes.',
        ],
    ],
    'conceitos' => [],
];
