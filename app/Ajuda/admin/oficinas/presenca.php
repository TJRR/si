<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Presença na oficina',
    'resumo' => 'Cruza, para um horário de oficina já encerrado, as três informações que o sistema tem sobre cada integrante: foi convidado, respondeu ao convite e entrou de fato na sala do Meet. Como a oficina reúne várias equipes na mesma sala, a tabela mostra a equipe de cada pessoa.',
    'operacoes' => [
        [
            'nome' => 'Convidados',
            'como' => 'Uma linha por integrante das equipes inscritas. "Convite" é a resposta ao convite do Google Agenda; "Entrou" e "Permanência" vêm da sala do Meet.',
            'observacao' => 'Permanência vazia com "Entrou: Sim" significa que a pessoa entrou mas o fim da sessão não foi registrado — não conte como zero.',
        ],
        [
            'nome' => 'Entraram sem identificação',
            'como' => 'Lista quem esteve na sala mas não foi reconhecido como convidado deste horário.',
            'observacao' => 'Não é uma lista de intrusos confirmados: um integrante legítimo que entrou com conta pessoal ou nome diferente do cadastro cai aqui também. Confira antes de concluir.',
        ],
        [
            'nome' => 'Reprocessar presença',
            'como' => 'Aparece quando a presença ficou indisponível. Devolve o horário à fila de captura automática.',
            'observacao' => 'Use depois de corrigir a causa da falha. Se vários horários falharam juntos, a causa costuma ser única (autorização do Google), não um problema por horário.',
        ],
    ],
    'conceitos' => ['presenca_meet', 'integracao_google_agenda'],
];
