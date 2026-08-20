<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Oficinas (admin)',
    'resumo' => 'Encontros coletivos com tema pré-definido, sem exclusividade — várias equipes podem se inscrever no mesmo horário. Mesmo padrão de Mentorias, sem a etapa de escolher um mentor específico.',
    'operacoes' => [
        [
            'nome' => 'Coluna "Restrito a"',
            'como' => '"Aberto a todos" = qualquer equipe do concurso vê o compromisso. Com o nome de uma trilha e etapa, só quem está habilitado àquela etapa vê e se inscreve. Vínculo definido no formulário do horário.',
        ],
        [
            'nome' => 'Editar',
            'como' => 'Aparece apenas enquanto o horário não começou. Depois da data de início, o botão de editar e o de remover somem — e o servidor recusa a ação mesmo se ela for forçada.',
        ],
        [
            'nome' => '+ Novo horário',
            'como' => 'Abre o formulário de um novo horário de oficina.',
        ],
        [
            'nome' => 'Nº de inscritas',
            'como' => 'Clique no número para abrir, em popup, a lista de equipes inscritas naquele horário.',
        ],
        [
            'nome' => 'Verificar/Tentar novamente',
            'como' => 'Reconsulta o Google Agenda para atualizar o status da integração e o RSVP dos convidados.',
        ],
        [
            'nome' => 'Presença na sala do Meet',
            'como' => 'Abre, em popup, quem de fato entrou na sala e por quanto tempo, cruzado com o convite e a resposta de cada um.',
            'observacao' => 'Só aparece em horários com integração ao Google Agenda e que já terminaram. A captura é automática e começa 2h depois do fim do horário.',
        ],
        [
            'nome' => 'Remover',
            'icone' => 'remover',
            'como' => 'Apaga o horário, com confirmação.',
            'observacao' => 'Se já houver equipes inscritas, todas são notificadas automaticamente.',
        ],
    ],
    'conceitos' => ['integracao_google_agenda', 'presenca_meet'],
];
