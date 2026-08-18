<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Equipes homologadas',
    'resumo' => 'Transparência: lista as equipes homologadas de uma trilha, com integrantes (líder marcado), sem nenhum dado sensível. Tela só de leitura.',
    'operacoes' => [],
    'conceitos' => ['publicar_trava'],
];
