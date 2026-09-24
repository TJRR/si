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
        ['nome' => 'Ícone', 'como' => 'Escolhido numa lista fechada (troféu, música, raio, prédio, microfone, lâmpada, calendário, pessoas, livro, computador), desenhado dentro de um quadrado na cor informada.'],
        ['nome' => 'Animação', 'como' => 'Os cartões aparecem em sequência quando a seção entra na tela e sobem um pouco ao passar o mouse. Quem prefere menos movimento no sistema operacional não vê animação.'],
    ],
    'conceitos' => ['reordenar_arraste'],
];
