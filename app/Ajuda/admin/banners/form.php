<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Faixa: novo/editar',
    'resumo' => 'Uma faixa de texto/imagem abaixo do carrossel de imagens.',
    'operacoes' => [
        [
            'nome' => 'Imagem ou cor sólida',
            'como' => 'Envio de imagem (1440×400) ou, na ausência dela, cor sólida de fundo.',
        ],
        [
            'nome' => 'Texto sobreposto',
            'como' => 'Editor rico, com opção de alinhamento.',
        ],
        [
            'nome' => 'Botão',
            'como' => 'Tipo de destino (Hiperlink interno/Hiperlink externo/Âncora/Arquivo/Vídeo), posição em grade 3×3 sobre a faixa, e efeito ao passar o mouse.',
        ],
        [
            'nome' => 'Ativo',
            'como' => 'Controla se a faixa aparece na página inicial.',
        ],
    ],
    'conceitos' => [],
];
