<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Tema: criar/editar',
    'resumo' => 'Um tema é um conjunto de 4 cores e 2 logos. Só temas customizados podem ser editados; os 5 temas de sistema são fixos (duplique um deles como ponto de partida).',
    'operacoes' => [
        [
            'nome' => 'Cores',
            'como' => 'Pré-visualização em tempo real ao lado de cada campo de cor.',
        ],
        [
            'nome' => 'Cor terciária',
            'como' => 'Base do degradê de fundo e acento dos avisos do aplicativo do Evento.',
        ],
        [
            'nome' => 'Fundo claro',
            'como' => 'Fundo dos cartões, do menu e da área de conteúdo do aplicativo do Evento. A cor do texto sobre esse fundo é calculada automaticamente (claro ou escuro, conforme a cor escolhida). Não precisa configurar separadamente.',
        ],
        [
            'nome' => 'Logo do Concurso',
            'como' => 'Aparece no portal público e no painel administrativo. Opcional: sem envio, o sistema usa a imagem padrão.',
        ],
        [
            'nome' => 'Logo do Evento',
            'como' => 'Aparece só na tela de entrada do aplicativo do Evento. Opcional: sem envio, o sistema usa o ícone do aplicativo.',
        ],
    ],
    'conceitos' => [],
];
