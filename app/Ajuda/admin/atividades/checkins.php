<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Atividade: Presenças',
    'resumo' => 'Quem confirmou presença nesta atividade, com o horário exato e se aquela confirmação conta como presença efetiva (dentro da janela definida em "Dados Gerais"). O participante confirma presença sozinho, apontando a câmera do aplicativo para o código fixo afixado no espaço físico. Não há confirmação manual pelo Administrador.',
    'operacoes' => [
        [
            'nome' => 'Presença efetiva',
            'como' => '"Sim" quando a confirmação ocorreu entre o início da atividade e o limite de tolerância definido em "Dados Gerais". "Não" quando ocorreu depois desse limite: a confirmação continua registrada, só não conta como efetiva.',
        ],
        [
            'nome' => 'Selo "Inscrição cancelada"',
            'como' => 'Aparece quando a pessoa confirmou presença e depois cancelou a inscrição nesta atividade. A presença já ocorrida permanece registrada; cancelar a inscrição não apaga o histórico.',
        ],
    ],
    'conceitos' => [],
];
