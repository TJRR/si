<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Mentorias (admin)',
    'resumo' => 'Horários de mentoria 1:1 — encontro individual entre uma equipe e um mentor específico, com exclusividade (a equipe pode ter no máximo 1 reserva ativa).',
    'operacoes' => [
        [
            'nome' => '+ Novo horário',
            'como' => 'Abre o formulário de um novo horário de mentoria.',
        ],
        [
            'nome' => 'Verificar/Tentar novamente',
            'como' => 'Reconsulta o Google Agenda para atualizar o status da integração e o RSVP dos convidados.',
        ],
        [
            'nome' => 'Remover',
            'icone' => 'remover',
            'como' => 'Apaga o horário, com confirmação.',
            'observacao' => 'Só quem criou o horário ou um Administrador pode remover. Se já houver reserva, a equipe é notificada automaticamente.',
        ],
    ],
    'conceitos' => ['integracao_google_agenda'],
];
