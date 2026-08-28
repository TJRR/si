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
            'como' => 'Texto rico (editor com formatação, cor, imagem, link) + imagem (alt obrigatório) → Salvar.',
            'observacao' => 'Só edita o que é exibido publicamente — não recalcula nota nem posição no ranking. O HTML digitado passa por uma limpeza de segurança ao salvar (remove script/handlers), sem afetar formatação normal.',
        ],
    ],
    'conceitos' => [],
];
