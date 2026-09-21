<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Apresentação de pitch',
    'resumo' => 'Agendamento da apresentação oral da sua equipe: escolha data, horário e modalidade (presencial ou online) dentro da janela definida pelo Admin.',
    'operacoes' => [
        [
            'nome' => 'Quem pode reservar',
            'como' => 'Qualquer integrante homologado da equipe, não só o líder.',
        ],
        [
            'nome' => 'Escolher horário',
            'como' => 'Selecione a modalidade e confirme pelo ícone; disponível só dentro da janela aberta pelo Admin.',
            'observacao' => 'A reserva pode falhar se outra equipe confirmar o mesmo horário no mesmo instante (duas equipes confirmaram ao mesmo tempo); nesse caso a tela mostra um aviso e o horário continua na lista para escolher outro. Sem reagendamento pelo próprio sistema depois de escolhido; em caso de necessidade, entre em contato com o ' . nomeUnidadeResponsavel() . '.',
        ],
        [
            'nome' => 'Se a janela fechar sem você escolher',
            'como' => 'O Administrador atribui um horário, sempre em modalidade online.',
        ],
        [
            'nome' => 'Entrar na sala (Google Meet)',
            'como' => 'Aparece só quando a modalidade é online e a integração já criou o hiperlink.',
            'observacao' => 'Se o horário foi reservado há pouco tempo, a sala pode ainda estar sendo gerada; atualize a página em alguns instantes.',
        ],
    ],
    'conceitos' => [],
];
