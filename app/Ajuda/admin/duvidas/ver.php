<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Dúvida — detalhe/atendimento',
    'resumo' => 'Thread completa de uma dúvida, com histórico de escalonamento e anexos da pergunta/respostas. Quem pode agir: Administrador com escopo sobre o concurso da dúvida, ou o responsável atual designado a ela.',
    'operacoes' => [
        [
            'nome' => 'Responder',
            'icone' => 'responder',
            'como' => 'Textarea + anexo opcional. Notifica a equipe que registrou a dúvida.',
            'observacao' => 'Uma dúvida "Respondida" trava — não é mais possível responder nem escalar a partir daqui (só o participante pode reabri-la).',
        ],
        [
            'nome' => 'Escalar',
            'icone' => 'escalar',
            'como' => 'Escolha outro atendente para assumir a responsabilidade.',
        ],
        [
            'nome' => 'Histórico de escalonamento',
            'icone' => 'historico',
            'como' => 'Mostra quem escalou para quem, e quando.',
        ],
    ],
    'conceitos' => ['atendimento_fila_escalonamento'],
];
