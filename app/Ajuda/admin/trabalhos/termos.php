<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Declarações da submissão',
    'resumo' => 'Cada declaração vira uma caixa de marcação no fim do formulário de submissão, na ordem desta lista.',
    'operacoes' => [
        ['nome' => 'Obrigatória', 'como' => 'O trabalho só é enviado com a caixa marcada.'],
        ['nome' => 'Ativa', 'como' => 'Desmarcada, a declaração some do formulário sem ser apagada, e os aceites antigos continuam guardados.'],
        ['nome' => 'Remover', 'icone' => 'remover', 'como' => 'Bloqueado quando a declaração já foi aceita em alguma submissão: nesse caso, desative em vez de apagar.'],
    ],
    'conceitos' => ['nunca_apaga_so_versiona'],
];
