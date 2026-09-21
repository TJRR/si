<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Destaque do caso de sucesso',
    'resumo' => 'Metadados de exibição pública de uma equipe vencedora (usado na página de Edições Anteriores/resultado).',
    'operacoes' => [
        [
            'nome' => 'Resumo de destaque / Imagem',
            'como' => 'Texto rico (editor com formatação, cor, imagem, hiperlink) + imagem (alt obrigatório) → Salvar.',
            'observacao' => 'Só edita o que é exibido publicamente: não recalcula nota nem posição no ranking. O texto digitado passa por uma limpeza de segurança ao salvar (remove trechos que representem risco de segurança), sem afetar formatação normal.',
        ],
    ],
    'conceitos' => [],
];
