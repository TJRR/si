<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Mentoria — novo horário',
    'resumo' => 'Criação de um horário de mentoria 1:1.',
    'operacoes' => [
        [
            'nome' => 'Mentor',
            'como' => 'Ao trocar o mentor selecionado, a elegibilidade para integração com Google Agenda é recalculada automaticamente (via JavaScript, sem recarregar a página).',
        ],
        [
            'nome' => 'Datas',
            'como' => 'Início e fim do horário de atendimento.',
        ],
        [
            'nome' => 'Integrar com Google Agenda',
            'como' => 'Ver conceito abaixo — só habilitado para mentores com e-mail @tjrr.jus.br, e mutuamente exclusivo com o link manual.',
        ],
        [
            'nome' => 'Link Meet manual',
            'como' => 'Só usado quando a integração está desmarcada.',
        ],
    ],
    'conceitos' => ['integracao_google_agenda'],
];
