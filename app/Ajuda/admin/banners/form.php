<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Banner — novo/editar',
    'resumo' => 'Uma faixa de texto/imagem abaixo do slideshow.',
    'operacoes' => [
        [
            'nome' => 'Imagem ou cor sólida',
            'como' => 'Upload de imagem (1440×400) ou, na ausência dela, cor sólida de fundo.',
        ],
        [
            'nome' => 'Texto sobreposto',
            'como' => 'Editor rico, com opção de alinhamento.',
        ],
        [
            'nome' => 'Botão',
            'como' => 'Tipo de destino (Link interno/externo/Âncora/Arquivo/Vídeo), posição em grade 3×3 sobre o banner, e efeito de hover.',
        ],
        [
            'nome' => 'Ativo',
            'como' => 'Controla se o banner aparece na home.',
        ],
    ],
    'conceitos' => [],
];
