<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Criar cadastro',
    'resumo' => 'Autocadastro de acesso ao sistema (nome, e-mail e senha, ou conta Google).',
    'operacoes' => [
        [
            'nome' => 'Cadastrar com Google',
            'como' => 'Cria a conta a partir dos dados da conta Google — não precisa definir senha.',
        ],
        [
            'nome' => 'Nome, e-mail e senha',
            'como' => 'Preencha os três campos e clique em "Cadastrar".',
        ],
    ],
    'conceitos' => ['cadastro_pendente_aprovacao'],
];
