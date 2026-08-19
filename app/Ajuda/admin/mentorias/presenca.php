<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Presença na mentoria',
    'resumo' => 'Cruza, para um horário de mentoria já encerrado, as três informações que o sistema tem sobre cada integrante: foi convidado, respondeu ao convite e entrou de fato na sala do Meet. A mentoria é exclusiva de uma equipe, então todos os convidados são da mesma equipe.',
    'operacoes' => [
        [
            'nome' => 'Convidados',
            'como' => 'Uma linha por integrante da equipe que reservou o horário. "Convite" é a resposta ao convite do Google Agenda; "Entrou" e "Permanência" vêm da sala do Meet.',
            'observacao' => 'Permanência vazia com "Entrou: Sim" significa que a pessoa entrou mas o fim da sessão não foi registrado — não conte como zero. Se o horário nunca foi reservado, não há convidados a listar.',
        ],
        [
            'nome' => 'Entraram sem identificação',
            'como' => 'Lista quem esteve na sala mas não foi reconhecido como convidado deste horário.',
            'observacao' => 'Não é uma lista de intrusos confirmados: o próprio mentor aparece aqui, por não ser integrante da equipe, assim como um integrante que entrou com conta pessoal ou nome diferente do cadastro.',
        ],
        [
            'nome' => 'Reprocessar presença',
            'como' => 'Aparece quando a presença ficou indisponível. Devolve o horário à fila de captura automática.',
            'observacao' => 'Use depois de corrigir a causa da falha. Se vários horários falharam juntos, a causa costuma ser única (autorização do Google), não um problema por horário.',
        ],
    ],
    'conceitos' => ['presenca_meet', 'integracao_google_agenda'],
];
