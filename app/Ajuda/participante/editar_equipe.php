<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Editar equipe',
    'resumo' => 'Dados gerais da equipe — só o líder tem acesso a esta tela (403 para os demais integrantes).',
    'operacoes' => [
        [
            'nome' => 'Nome / Vínculo institucional / Observações',
            'como' => 'Preencha e clique em Salvar.',
        ],
    ],
    'conceitos' => [],
];
