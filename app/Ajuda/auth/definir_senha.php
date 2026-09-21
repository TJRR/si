<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Definir senha',
    'resumo' => 'Tela de redefinição de senha, acessada pelo hiperlink recebido por e-mail (código de uso único).',
    'operacoes' => [
        [
            'nome' => 'Nova senha / Confirme a senha',
            'como' => 'Mínimo de 8 caracteres, os dois campos precisam ser iguais. Clique em "Salvar senha".',
            'observacao' => 'Definir uma senha é opcional: dá para continuar usando só "Entrar com Google", se preferir. Se o hiperlink já foi usado ou expirou, o formulário inteiro fica bloqueado com uma mensagem de erro; peça um novo hiperlink em "Esqueci minha senha".',
        ],
    ],
    'conceitos' => [],
];
