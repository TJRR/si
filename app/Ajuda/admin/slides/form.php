<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Imagem do carrossel (novo/editar)',
    'resumo' => 'Uma imagem do carrossel do topo da home.',
    'operacoes' => [
        [
            'nome' => 'Imagem',
            'como' => 'Envio único: o sistema gera automaticamente as versões computador (1440×800) e celular (768×800). Alt (texto alternativo) é obrigatório.',
        ],
        [
            'nome' => 'Cor de fundo',
            'como' => 'Usada como cor reserva enquanto a imagem carrega, ou se não houver imagem.',
        ],
        [
            'nome' => 'Camada de sobreposição',
            'como' => 'Nenhum, Escurecer, Vinheta, Pontos, Linhas, Pontos vazados ou Trama, com opacidade e cor configuráveis, sobreposto à imagem.',
        ],
        [
            'nome' => 'Duração e transição',
            'como' => 'Duração de 1 a 30 segundos; transição Fade, Deslizar ou Zoom.',
        ],
        [
            'nome' => 'Título e botão de chamada para ação (CTA)',
            'como' => 'Título em editor rico. Botão com título, hiperlink, aba de destino, cores, tamanho, efeito ao passar o mouse e animação, todos configuráveis.',
            'observacao' => 'Não é possível salvar um título de botão sem preencher o hiperlink.',
        ],
    ],
    'conceitos' => [],
];
