<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Oficina — novo horário',
    'resumo' => 'Criação de um horário de oficina. O organizador é sempre quem está logado — não há seleção de mentor.',
    'operacoes' => [
        [
            'nome' => 'Tema',
            'como' => 'Obrigatório — é o texto exibido para as equipes escolherem se inscrever.',
        ],
        [
            'nome' => 'Datas',
            'como' => 'Início e fim do horário.',
        ],
        [
            'nome' => 'Integrar com Google Agenda',
            'como' => 'Ver conceito abaixo.',
        ],
        [
            'nome' => 'Link Meet manual',
            'como' => 'Só usado quando a integração está desmarcada.',
        ],
    ],
    'conceitos' => ['integracao_google_agenda'],
];
