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
            'nome' => 'Remover',
            'icone' => 'remover',
            'como' => 'Apaga o horário, com confirmação.',
            'observacao' => 'Se já houver equipes inscritas, todas são notificadas automaticamente.',
        ],
    ],
    'conceitos' => ['integracao_google_agenda'],
];
