<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Esqueci minha senha',
    'resumo' => 'Solicita um link de redefinição de senha por e-mail.',
    'operacoes' => [
        [
            'nome' => 'Enviar link de redefinição',
            'como' => 'Informe o e-mail da conta e clique no botão.',
            'observacao' => 'A resposta na tela é sempre a mesma, exista ou não uma conta com aquele e-mail — isso é proposital, para não revelar quem tem cadastro no sistema.',
        ],
    ],
    'conceitos' => [],
];
