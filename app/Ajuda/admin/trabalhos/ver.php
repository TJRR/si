<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Trabalho: detalhe',
    'resumo' => 'Dados completos de um trabalho submetido - autoria, conteúdo, avaliadores designados e desclassificação.',
    'operacoes' => [
        [
            'nome' => 'Designar avaliador',
            'como' => 'Escolha entre os avaliadores já convidados para este evento. Bloqueado se a pessoa escolhida for autora (principal ou coautora) deste mesmo trabalho.',
        ],
        [
            'nome' => 'Remover designação',
            'como' => 'Desfaz a designação individual daquele avaliador para este trabalho, sem tirá-lo do pool geral de avaliadores do evento.',
        ],
        [
            'nome' => 'Aviso de avaliadores insuficientes',
            'como' => 'Aparece quando a quantidade de avaliadores designados fica abaixo do configurado nas Configurações do evento.',
        ],
        [
            'nome' => 'Desclassificar',
            'como' => 'Exige um motivo. Trabalho desclassificado sai da disputa, mas continua registrado com o motivo visível aqui.',
        ],
    ],
    'conceitos' => ['sigilo_anonimato'],
];
