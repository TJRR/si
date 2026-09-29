<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Alterar senha',
    'resumo' => 'Troca da senha da sua conta, sem sair do aplicativo. Só existe para quem entra com senha: quem entra pela conta Google não tem senha a trocar.',
    'operacoes' => [
        [
            'nome' => 'Senha atual',
            'como' => 'Confirma que é você quem está trocando. Sem ela, nada muda.',
        ],
        [
            'nome' => 'Nova senha',
            'como' => 'Mínimo de 8 caracteres, digitada duas vezes e diferente da atual.',
            'observacao' => 'Depois da troca, qualquer endereço de definir senha que você tenha recebido antes deixa de valer.',
        ],
    ],
    'conceitos' => [],
];
