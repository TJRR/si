<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Edição Anterior — detalhe',
    'resumo' => 'Detalhe de uma edição encerrada: descrição da edição (texto rico), vencedores por trilha (com case), documentos, galeria e um bloco de conteúdo extra opcional específico desta edição (ex.: "Classificação Geral"). Os vencedores só aparecem se o resultado final da trilha foi publicado; o vídeo do case é buscado automaticamente na submissão mais recente da equipe vencedora; o bloco extra só aparece se cadastrado e ativo para esta edição.',
    'operacoes' => [],
    'conceitos' => [],
];
