<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Perguntas frequentes do Evento',
    'resumo' => 'Perguntas e respostas em acordeão na página do evento, com cadastro próprio deste evento.',
    'operacoes' => [
        ['nome' => 'Ativa', 'como' => 'Desmarcada, a pergunta some da página sem ser apagada.'],
        ['nome' => 'Resposta', 'como' => 'Aceita texto simples; a formatação rica fica por conta do texto de apoio da seção.'],
    ],
    'conceitos' => ['reordenar_arraste'],
];
