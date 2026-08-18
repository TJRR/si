<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Mentorias (transparência pública)',
    'resumo' => 'Lista de horários de mentoria, só para transparência — mostra mentor, período e se o horário está Vago ou Reservado, sem identificar qual equipe reservou nem nenhum dado pessoal. O agendamento em si é feito pelo painel de cada equipe, não por aqui.',
    'operacoes' => [],
    'conceitos' => [],
];
