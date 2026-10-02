<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Extrato de pontos',
    'resumo' => 'O detalhe dos pontos de uma pessoa, origem por origem, para responder a uma dúvida ou conferir uma reclamação.',
    'operacoes' => [
        [
            'nome' => 'Total válido',
            'como' => 'O mesmo total da classificação, com a divisão por origem. Com a gincana encerrada, é o total congelado no encerramento.',
        ],
        [
            'nome' => 'Listas de cada origem',
            'como' => 'Presenças pontuadas (com o extra de pontualidade), participações em competições, bônus, visitas a estandes e comprovações de divulgação, incluindo as anuladas, com o motivo.',
            'observacao' => 'Conexões aparecem só em total: a administração não vê quem se conectou com quem, por decisão registrada no módulo Conexões.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin'],
];
