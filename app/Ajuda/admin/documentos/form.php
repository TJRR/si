<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Documento — novo',
    'resumo' => 'Upload de um documento novo (ou de uma nova versão de um já existente).',
    'operacoes' => [
        [
            'nome' => 'Tipo / Trilha / Título / PDF',
            'como' => 'Trilha é opcional (documento pode ser geral do concurso). Enviar com o mesmo tipo + título de um documento já existente cria uma nova versão dele.',
        ],
    ],
    'conceitos' => ['nunca_apaga_so_versiona'],
];
