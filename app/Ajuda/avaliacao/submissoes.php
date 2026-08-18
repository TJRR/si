<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Submissões da etapa',
    'resumo' => 'Lista de submissões desta etapa que estão sob sua responsabilidade, com uma etiqueta própria de progresso por submissão ("X/Y critérios", ou "Resultado publicado" quando a etapa já foi encerrada) — visual diferente do status-pill comum do resto do sistema, específico desta fila do avaliador.',
    'operacoes' => [
        [
            'nome' => 'Lançar notas / Ver notas',
            'como' => 'Abre a tela de avaliação da submissão — o rótulo muda para "Ver notas" (somente leitura) depois que o resultado da etapa é publicado.',
            'observacao' => 'Em sigilo cego, o nome da equipe aparece como "Equipe {número}" (estável, nunca recalculado a partir da ordem de envio). Submissões 100% avaliadas por você somem desta lista.',
        ],
    ],
    'conceitos' => ['sigilo_anonimato'],
];
