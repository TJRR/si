<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Cadastro de competição',
    'resumo' => 'Define uma competição ou experiência do evento: nome, atividade em que acontece, pontos e regras.',
    'operacoes' => [
        [
            'nome' => 'Nome',
            'como' => 'O que o participante vê nas Regras do jogo, no extrato e na mensagem de quando pontua.',
        ],
        [
            'nome' => 'Atividade da programação',
            'como' => 'A atividade em que a competição acontece, por exemplo a "Batalha de Prompts" da programação. Ela dá a janela da leitura (da abertura da leitura da atividade até o fim) e diz quem é o facilitador que vê o código em tela cheia e não pontua na competição.',
        ],
        [
            'nome' => 'Pontos por participação',
            'como' => 'Quanto vale participar, uma vez por pessoa. O valor fica congelado no momento da leitura: mudar aqui vale só para as próximas participações.',
        ],
        [
            'nome' => 'Regras',
            'como' => 'Texto com formatação que aparece para o participante nas Regras do jogo.',
        ],
        [
            'nome' => 'Competição ativa',
            'como' => 'Desativada, o código deixa de valer e a competição some das Regras do jogo, sem apagar as participações já registradas.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin'],
];
