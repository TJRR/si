<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Campo do formulário de inscrição',
    'resumo' => 'Tipo "Texto" vira um campo livre; tipo "Lista de opções" vira uma lista suspensa: digite uma opção por linha no campo que aparece ao escolher esse tipo. O texto de ajuda é opcional e aparece abaixo do campo, na tela pública, explicando o que preencher.',
    'operacoes' => [
        ['nome' => 'Salvar', 'como' => 'Grava o campo.'],
    ],
    'conceitos' => [],
];
