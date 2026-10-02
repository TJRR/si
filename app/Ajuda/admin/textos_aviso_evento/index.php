<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Textos dos avisos do Evento',
    'resumo' => 'Lista dos avisos que o sistema manda por correio eletrônico nos eventos, com a indicação de quais já têm texto próprio. Aviso sem texto próprio sai com o texto padrão do sistema. Os avisos do Concurso não aparecem aqui.',
    'operacoes' => [
        [
            'nome' => 'Editar o texto',
            'como' => 'Abre o assunto e o texto do aviso para edição. Na primeira vez, o formulário já vem com o texto que o sistema usa hoje.',
        ],
        [
            'nome' => 'Texto próprio e texto padrão',
            'como' => '"Texto próprio" quer dizer que o aviso sai com o texto salvo nesta tela. "Texto padrão" quer dizer que ninguém mudou o texto ainda.',
        ],
    ],
    'conceitos' => [],
];
