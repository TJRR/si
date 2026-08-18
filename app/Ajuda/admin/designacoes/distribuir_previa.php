<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Distribuição automática — prévia',
    'resumo' => 'Prévia de quem seria designado para cada submissão, antes de gravar de fato — nada é salvo até você confirmar.',
    'operacoes' => [
        [
            'nome' => 'Trocar avaliador sugerido',
            'como' => 'Use o seletor em qualquer linha para substituir a sugestão automática.',
        ],
        [
            'nome' => 'Confirmar distribuição',
            'como' => 'Grava as designações mostradas na prévia (já com as trocas manuais que você tiver feito).',
            'observacao' => '"Distribuição automática balanceada" não trava contra remoção depois — diferente do sorteio por categoria, uma designação feita assim pode ser removida normalmente na tela de Designações.',
        ],
    ],
    'conceitos' => [],
];
