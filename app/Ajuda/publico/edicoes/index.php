<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Edições Anteriores',
    'resumo' => 'Repositório histórico de concursos já encerrados — período, totais e link para o detalhe de cada edição.',
    'operacoes' => [],
    'conceitos' => [],
];
