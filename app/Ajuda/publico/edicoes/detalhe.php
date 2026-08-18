<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Edição Anterior — detalhe',
    'resumo' => 'Detalhe de uma edição encerrada: vencedores por trilha (com case), documentos e galeria. Os vencedores só aparecem se o resultado final da trilha foi publicado; o vídeo do case é buscado automaticamente na submissão mais recente da equipe vencedora.',
    'operacoes' => [],
    'conceitos' => [],
];
