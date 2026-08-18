<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Critério — novo/editar',
    'resumo' => 'Dados de um critério de avaliação.',
    'operacoes' => [
        [
            'nome' => 'Código',
            'como' => 'Sugerido automaticamente; precisa ser único dentro da etapa — é a variável usada na Fórmula da Etapa.',
        ],
        [
            'nome' => 'Nome / Descrição / Peso / Escala mín-máx',
            'como' => 'A escala define o intervalo de nota aceito na tela de avaliação.',
        ],
        [
            'nome' => 'Campos do formulário nesta aba',
            'como' => 'Checkboxes que escolhem quais campos da submissão aparecem na aba deste critério na tela do avaliador.',
            'observacao' => 'Se nenhum campo for marcado, a aba mostra a ficha inteira. Se todos os critérios da etapa tiverem exatamente o mesmo conjunto de campos marcado, a tela do avaliador muda de "abas" para o layout "compartilhado" (grade) automaticamente.',
        ],
    ],
    'conceitos' => [],
];
