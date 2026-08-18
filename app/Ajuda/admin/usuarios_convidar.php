<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Convidar usuário',
    'resumo' => 'Cria um acesso diretamente, sem passar pelo fluxo de autocadastro pendente.',
    'operacoes' => [
        [
            'nome' => 'Nome, e-mail, perfil (obrigatório), concurso, categoria',
            'como' => 'Categoria só aparece quando o perfil é avaliador.',
            'observacao' => 'Se o e-mail já existir no sistema, o convite não cria uma conta nova — só adiciona/substitui o perfil no cadastro existente, sem enviar novo e-mail de convite.',
        ],
    ],
    'conceitos' => [],
];
