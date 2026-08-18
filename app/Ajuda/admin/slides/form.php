<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Slide — novo/editar',
    'resumo' => 'Um slide do carrossel do topo da home.',
    'operacoes' => [
        [
            'nome' => 'Imagem',
            'como' => 'Upload único — o sistema gera automaticamente as versões desktop (1440×800) e mobile (768×800). Alt (texto alternativo) é obrigatório.',
        ],
        [
            'nome' => 'Cor de fundo',
            'como' => 'Usada como fallback enquanto a imagem carrega, ou se não houver imagem.',
        ],
        [
            'nome' => 'Overlay',
            'como' => 'Nenhum, Escurecer, Vinheta, Pontos, Linhas, Pontos vazados ou Trama — com opacidade e cor configuráveis, sobreposto à imagem.',
        ],
        [
            'nome' => 'Duração e transição',
            'como' => 'Duração de 1 a 30 segundos; transição Fade, Deslizar ou Zoom.',
        ],
        [
            'nome' => 'Título e botão CTA',
            'como' => 'Título em editor rico. Botão com título, link, aba de destino, cores, tamanho, hover e animação, todos configuráveis.',
            'observacao' => 'Não é possível salvar um título de botão sem preencher o link.',
        ],
    ],
    'conceitos' => [],
];
