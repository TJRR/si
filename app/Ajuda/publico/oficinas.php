<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Oficinas (transparência pública)',
    'resumo' => 'Lista de horários de oficina, só para transparência — mostra o nome das equipes inscritas em cada horário (sem outro dado pessoal). A inscrição em si é feita pelo painel de cada equipe, não por aqui.',
    'operacoes' => [],
    'conceitos' => [],
];
