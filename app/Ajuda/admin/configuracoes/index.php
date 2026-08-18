<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Configurações',
    'resumo' => 'Ajustes globais do sistema: tempo de expiração de sessão por inatividade e o modo de manutenção.',
    'operacoes' => [
        [
            'nome' => 'Tempo de expiração de sessão',
            'como' => 'Minutos de inatividade até o logout automático.',
        ],
        [
            'nome' => 'Desativar sistema',
            'como' => 'Bloqueia o acesso e desconecta todos os usuários, exceto administradores, imediatamente.',
            'observacao' => 'Confirmação reforçada, avisando o efeito exato — use só durante uma atualização. "Reativar sistema" desfaz, com aviso de que o acesso normal volta para todos na hora.',
        ],
    ],
    'conceitos' => [],
];
