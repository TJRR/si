<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Alterar senha',
    'resumo' => 'Troca a senha da sua própria conta. Só se aplica a contas com login por senha — quem acessa só pelo Google não tem senha cadastrada.',
    'operacoes' => [
        [
            'nome' => 'Senha atual',
            'como' => 'Obrigatória para confirmar que é você mesmo alterando a senha.',
        ],
        [
            'nome' => 'Nova senha / Confirme a nova senha',
            'como' => 'Mínimo de 8 caracteres, os dois campos precisam ser iguais.',
        ],
    ],
    'conceitos' => [],
];
