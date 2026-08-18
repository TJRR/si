<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Dúvidas — minhas escaladas',
    'resumo' => 'Fila pessoal de dúvidas sob sua responsabilidade agora — é a tela principal de quem tem o perfil Colaborador (que não acessa o Painel completo).',
    'operacoes' => [
        [
            'nome' => 'Filtro de status',
            'como' => 'Reduz a lista por situação da dúvida.',
        ],
        [
            'nome' => 'Ver / Responder / Escalar',
            'icone' => 'ver',
            'como' => 'Âncoras que já abrem direto no ponto certo da tela de detalhe.',
        ],
    ],
    'conceitos' => ['atendimento_fila_escalonamento'],
];
