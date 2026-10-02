<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Desempate',
    'resumo' => 'Cascata de critérios que decide a ordem de quem tem o mesmo total na classificação geral. O Administrador escolhe quais critérios usar e em que ordem.',
    'operacoes' => [
        [
            'nome' => 'Acrescentar critério',
            'como' => 'Entra no fim da cascata. Cada critério tem a sua direção fixa: "mais pontos de presença" vence quem tem mais; "quem chegou primeiro" vence quem atingiu a pontuação antes; "inscrição mais antiga" vence quem se inscreveu antes.',
        ],
        [
            'nome' => 'Arrastar ou setas',
            'como' => 'Mudam a ordem da cascata.',
        ],
        [
            'nome' => 'Retirar',
            'como' => 'Tira o critério da cascata.',
        ],
        [
            'nome' => 'Esgotada a cascata',
            'como' => 'As pessoas empatadas dividem a posição, e a lista da aba Classificação marca o empate. "Inscrição mais antiga" sempre desempata; use-o por último quando a organização precisar de uma ordem sem empate.',
            'observacao' => 'Depois do encerramento da gincana, a cascata fica travada: mudá-la mudaria a ordem final e quem entra entre os primeiros.',
        ],
    ],
    'conceitos' => ['reordenar_arraste', 'permissao_suporte_admin'],
];
