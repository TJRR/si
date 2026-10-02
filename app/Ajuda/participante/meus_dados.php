<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Meus dados',
    'resumo' => 'Nesta tela cada integrante confere e corrige os próprios dados pessoais. Ninguém edita os dados de outra pessoa, nem o líder.',
    'operacoes' => [
        [
            'nome' => 'Passo a passo',
            'como' => '1. Corrija o nome, o CPF ou o telefone. 2. Clique em "Salvar". O CPF é conferido antes do envio: se algum número estiver errado, a tela avisa na hora.',
            'observacao' => 'Mudar o CPF faz a sua participação voltar para "pendente" até a organização conferir o dado novo. O e-mail não muda por aqui, porque é ele que identifica a sua conta.',
        ],
    ],
    'conceitos' => [],
];
