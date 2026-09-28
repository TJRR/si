<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Atualizar estande',
    'resumo' => 'Nome, descrição e logotipo do estande, que aparecem para os participantes no aplicativo e na página do evento. Código, pontuação, categoria e situação do estande são definidos pela organização do evento.',
    'operacoes' => [
        ['nome' => 'Descrição', 'como' => 'Texto com formatação simples (negrito, listas, endereços). Imagens entram só pelo campo de logotipo.'],
        ['nome' => 'Logotipo', 'como' => 'JPG, PNG, WEBP ou GIF de até 4 MB. O texto alternativo descreve a imagem para quem usa leitor de tela e é obrigatório quando há logotipo.'],
    ],
    'conceitos' => [],
];
