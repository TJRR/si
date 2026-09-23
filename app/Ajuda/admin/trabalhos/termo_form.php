<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Editar declaração',
    'resumo' => 'Texto que a pessoa lê e aceita ao enviar o trabalho.',
    'operacoes' => [
        ['nome' => 'Alterar o texto', 'como' => 'Vale para as próximas submissões. O que já foi aceito fica guardado exatamente como estava no dia do envio.'],
    ],
    'conceitos' => ['nunca_apaga_so_versiona'],
];
