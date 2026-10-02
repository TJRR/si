<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Minhas facilitações',
    'resumo' => 'Atividades deste evento em que você foi designado facilitador (instrutor, professor, palestrante), e as competições que você conduz.',
    'operacoes' => [
        [
            'nome' => 'Código de presença pela internet',
            'como' => 'Aparece só quando a atividade aceita participação pela internet. Informe esse código assim que a sala virtual abrir, antes do início: quem participa à distância digita o código no aplicativo e pontua como quem está na sala, inclusive com o extra de pontualidade.',
        ],
        [
            'nome' => 'Competições que você conduz',
            'como' => 'Aparecem quando a organização ligou uma competição (por exemplo, o karaokê ou a Batalha de Prompts) a uma atividade que você facilita. Mostre o código a quem cantar ou competir, na hora da participação: a pessoa lê com "Ler código" no aplicativo dela. A lupa amplia o código em tela cheia.',
            'observacao' => 'Você conduz a competição, então a participação nela não pontua para você, e a sua presença na atividade que você facilita fica registrada sem pontos.',
        ],
    ],
    'conceitos' => [],
];
