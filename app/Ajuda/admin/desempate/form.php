<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Nova regra de desempate',
    'resumo' => 'Configuração de uma regra de desempate.',
    'operacoes' => [
        [
            'nome' => 'Tipo',
            'como' => 'Nota de critério (compara a nota de um critério específico) ou Data de inscrição (compara quem se inscreveu primeiro).',
        ],
        [
            'nome' => 'Critério',
            'como' => 'Só aparece quando o tipo é "Nota de critério" — escolha qual.',
        ],
        [
            'nome' => 'Direção',
            'como' => 'Decrescente (maior valor vence) ou Crescente (menor valor vence — use Crescente para "Data de inscrição", já que inscrever primeiro deve favorecer a equipe).',
        ],
    ],
    'conceitos' => [],
];
