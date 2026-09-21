<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Regras de desempate',
    'resumo' => 'Decide, em cascata, como desempatar dois trabalhos com a mesma nota final - só entra em uso quando a nota final empatar de fato.',
    'operacoes' => [
        [
            'nome' => 'Nova regra',
            'como' => 'Escolha "Maior nota em um critério" (indicando qual) ou "Data de submissão mais antiga", e a direção (maior valor vence ou menor valor vence).',
        ],
        [
            'nome' => 'Ordem das regras',
            'como' => 'As regras são aplicadas na ordem cadastrada - a primeira que desfizer o empate decide; se nenhuma desfizer, os trabalhos continuam empatados.',
        ],
        [
            'nome' => 'Remover',
            'como' => 'Remove a regra da cascata; as demais continuam valendo na mesma ordem relativa.',
        ],
    ],
    'conceitos' => [],
];
