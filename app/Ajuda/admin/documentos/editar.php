<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Documento — editar metadados',
    'resumo' => 'Altera tipo, trilha e título de um documento já cadastrado — não é possível trocar o arquivo aqui.',
    'operacoes' => [],
    'conceitos' => ['nunca_apaga_so_versiona'],
];
