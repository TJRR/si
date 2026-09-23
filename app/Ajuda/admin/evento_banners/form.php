<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Faixa do Evento, nova/editar',
    'resumo' => 'Uma faixa da página pública deste evento.',
    'operacoes' => [
        ['nome' => 'Imagem de fundo', 'como' => 'Opcional: sem imagem, a cor de fundo prevalece.'],
        ['nome' => 'Texto sobreposto', 'como' => 'Editor rico, com alinhamento configurável.'],
        ['nome' => 'Botão de ação', 'como' => 'Destino pode ser um hiperlink interno, externo, uma âncora da própria página, um arquivo ou um vídeo.'],
    ],
    'conceitos' => [],
];
