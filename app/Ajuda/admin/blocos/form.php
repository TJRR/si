<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Bloco de Conteúdo: editar',
    'resumo' => 'Conteúdo de uma seção de texto + imagem da página inicial.',
    'operacoes' => [
        [
            'nome' => 'Título / Âncora',
            'como' => 'A âncora é o hiperlink do menu superior para essa seção, travada nos blocos padrão.',
        ],
        [
            'nome' => 'Conteúdo',
            'como' => 'Editor rico com envio de imagem inline.',
        ],
        [
            'nome' => 'Imagem principal',
            'como' => '900×900, alt obrigatório, posição esquerda ou direita em relação ao texto.',
        ],
        [
            'nome' => 'Botão opcional / Ativo / Adicionar no menu superior / Mostrar no rodapé',
            'como' => 'Configurações de exibição do bloco.',
        ],
    ],
    'conceitos' => [],
];
