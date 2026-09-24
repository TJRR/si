<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Documento do evento: editar',
    'resumo' => 'Altera tipo e título de um documento já cadastrado. Não é possível trocar o arquivo aqui: para isso, envie um novo documento com o mesmo tipo e título.',
    'operacoes' => [],
    'conceitos' => ['nunca_apaga_so_versiona'],
];
