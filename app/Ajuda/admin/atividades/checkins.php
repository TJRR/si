<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Atividade: Presenças',
    'resumo' => 'Quem confirmou presença nesta atividade, com o horário exato e se aquela confirmação conta como presença efetiva (dentro da janela definida em "Dados Gerais"). O participante confirma presença sozinho, apontando a câmera do aplicativo para o código fixo afixado no espaço físico. O Administrador não confirma presença por ninguém; o que ele pode fazer, desde a Fase 57, é remover uma presença registrada por engano.',
    'operacoes' => [
        [
            'nome' => 'Presença efetiva',
            'como' => '"Sim" quando a confirmação ocorreu entre o início da atividade e o limite de tolerância definido em "Dados Gerais". "Não" quando ocorreu depois desse limite: a confirmação continua registrada, só não conta como efetiva.',
        ],
        [
            'nome' => 'Selo "Inscrição cancelada"',
            'como' => 'Aparece quando a pessoa confirmou presença e depois cancelou a inscrição nesta atividade. A presença já ocorrida permanece registrada; cancelar a inscrição não apaga o histórico.',
        ],
        [
            'nome' => 'Remover uma presença',
            'como' => 'Só Administrador, e só com um motivo escrito. É para o caso de a presença ter sido registrada por engano, por exemplo quem leu o código de uma sala em que não ficou.',
            'observacao' => 'A linha não é apagada: ela continua nesta tela, com o motivo e quem removeu, e some das contagens e das duas exportações. Uma exportação gerada depois da remoção sai diferente de uma gerada antes.',
        ],
        [
            'nome' => 'O que acontece com os bônus',
            'como' => 'Na hora da remoção, o sistema confere os bônus daquela pessoa e anula os que perderam a quantidade exigida, avisando ela pelo sino. Essa anulação é do sistema, e por isso é desfeita sozinha: se a pessoa confirmar presença de novo naquela atividade, o bônus volta.',
            'observacao' => 'Anulação feita à mão pelo Administrador, na tela de Bônus, é diferente: aquela nunca volta sozinha.',
        ],
        [
            'nome' => 'Confirmar presença de novo',
            'como' => 'Quem teve a presença removida continua podendo confirmar presença naquela atividade pelo aplicativo, e o registro volta com o horário da leitura nova.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin'],
];
