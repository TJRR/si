<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Biblioteca de Mídia',
    'resumo' => 'Repositório global de imagens, PDFs e vídeos, reaproveitável em qualquer edição, mesmo conceito de banco global do FAQ.',
    'operacoes' => [
        [
            'nome' => 'Pastas',
            'como' => 'Criar, renomear e apagar pastas, inclusive dentro de outras. A biblioteca continua sendo a raiz: tudo o que já existia fica lá até ser movido. Pasta com conteúdo dentro não é apagada.',
        ],
        [
            'nome' => 'Mover para',
            'como' => 'Move a mídia para outra pasta ou de volta para a raiz. Nenhum endereço de arquivo muda: a pasta é só organização.',
        ],
        [
            'nome' => 'Filtrar por tipo',
            'como' => 'Reduz a lista para imagem, PDF ou vídeo.',
        ],
        [
            'nome' => 'Abrir em nova aba',
            'como' => 'Visualiza o arquivo original.',
        ],
        [
            'nome' => 'Remover',
            'icone' => 'remover',
            'como' => 'Apaga o item.',
            'observacao' => 'Bloqueado com erro se o item ainda estiver em uso, por exemplo na galeria de fotos de uma edição.',
        ],
        [
            'nome' => '+ Nova mídia',
            'como' => 'Tipo, arquivo, alt obrigatório para imagem, título/descrição, e edição de origem opcional (marca de qual concurso o item veio, sem restringir o uso).',
        ],
    ],
    'conceitos' => ['banco_global_vs_edicao'],
];
