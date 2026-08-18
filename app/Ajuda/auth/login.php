<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Entrar',
    'resumo' => 'Porta de entrada do sistema — login por e-mail e senha ou por conta Google.',
    'operacoes' => [
        [
            'nome' => 'Entrar com Google',
            'como' => 'Login via conta Google — não precisa ter senha cadastrada no sistema.',
        ],
        [
            'nome' => 'E-mail e senha',
            'como' => 'Preencha os dois campos e clique em "Entrar".',
            'observacao' => 'A mensagem de erro nunca diz se o problema foi "e-mail não existe" ou "senha errada" — isso é proposital, por segurança.',
        ],
        [
            'nome' => 'Esqueci minha senha',
            'como' => 'Leva à tela de redefinição de senha por e-mail.',
        ],
        [
            'nome' => 'Cadastre-se',
            'como' => 'Leva ao formulário de autocadastro, para quem ainda não tem conta.',
        ],
    ],
    'conceitos' => [],
];
