<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Desempate',
    'resumo' => 'Critérios de desempate aplicados quando duas equipes empatam na Nota Final, configurados por etapa e aplicados em cascata — a primeira regra da lista tem prioridade sobre as seguintes.',
    'operacoes' => [
        [
            'nome' => '+ Nova regra',
            'como' => 'Adiciona uma regra de desempate para a etapa escolhida.',
        ],
        [
            'nome' => 'Mover cima/baixo',
            'icone' => 'mover_cima',
            'como' => 'Muda a prioridade entre as regras.',
        ],
        [
            'nome' => 'Remover',
            'icone' => 'remover',
            'como' => 'Apaga a regra.',
        ],
    ],
    'conceitos' => [],
];
