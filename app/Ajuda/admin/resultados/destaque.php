<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Destaque do case',
    'resumo' => 'Metadados de exibição pública de uma equipe vencedora (usado na página de Edições Anteriores/resultado).',
    'operacoes' => [
        [
            'nome' => 'Resumo de destaque / Imagem',
            'como' => 'Texto livre + imagem (alt obrigatório) → Salvar.',
            'observacao' => 'Só edita o que é exibido publicamente — não recalcula nota nem posição no ranking.',
        ],
    ],
    'conceitos' => [],
];
