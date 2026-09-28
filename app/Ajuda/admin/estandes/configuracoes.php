<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Configurações de Estandes',
    'resumo' => 'Texto que acompanha o convite enviado por e-mail ao representante de cada estande deste evento.',
    'operacoes' => [
        [
            'nome' => 'Texto do convite ao representante',
            'como' => 'Entra logo depois da apresentação, em todos os convites deste evento. O e-mail sempre traz, gerados pelo sistema, a saudação, o nome do estande e do evento, o que o representante pode fazer e como acessar. Em branco, vai só esse texto padrão.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin'],
];
