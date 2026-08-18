<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Meus dados',
    'resumo' => 'Dados pessoais de um integrante — cada um só edita os próprios dados, inclusive o líder.',
    'operacoes' => [
        [
            'nome' => 'Nome / CPF / Telefone',
            'como' => 'CPF é validado no navegador (dígitos verificadores) antes de enviar.',
            'observacao' => 'Alterar o CPF volta automaticamente o vínculo do integrante para "pendente" — o Suporte precisa conferir de novo. E-mail é travado, não editável por aqui.',
        ],
    ],
    'conceitos' => [],
];
