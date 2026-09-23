<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Destaques',
    'resumo' => 'Cartões curtos com o que a organização quer evidenciar na página.',
    'operacoes' => [
        ['nome' => 'De onde vêm os destaques', 'como' => 'No modo vinculado, a seção lista as Atividades marcadas com "destacar na página". No modo digitado, usa os itens cadastrados aqui.'],
        ['nome' => 'Atividade', 'como' => 'Vinculando um item a uma atividade, os dados dela aparecem; o que você digitar no item vence o dado da atividade.'],
    ],
    'conceitos' => ['reordenar_arraste'],
];
