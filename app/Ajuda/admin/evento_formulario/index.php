<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Formulário de inscrição do evento',
    'resumo' => 'Campos que aparecem na tela pública de inscrição deste evento, além do campo "Documento" (estrutural, sempre presente, não aparece nesta lista). Cada campo tem tipo, obrigatoriedade, ordem e um texto de ajuda opcional exibido abaixo dele na tela pública.',
    'operacoes' => [
        ['nome' => '+ Novo campo', 'como' => 'Abre o formulário de um campo novo.'],
        ['nome' => 'Mover ▲ / ▼', 'como' => 'Reordena o campo: é a ordem em que ele aparece na inscrição pública.'],
        ['nome' => 'Editar', 'icone' => 'editar', 'como' => 'Altera rótulo, tipo, obrigatoriedade, opções (se for lista) e texto de ajuda.'],
        ['nome' => 'Remover', 'icone' => 'remover', 'como' => 'Remove o campo do formulário.'],
    ],
    'conceitos' => [],
];
