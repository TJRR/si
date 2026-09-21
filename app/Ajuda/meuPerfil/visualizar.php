<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Visualizar como outro usuário',
    'resumo' => 'Entra no sistema com a visão de outro usuário, para conferir exatamente o que ele vê; útil para dar suporte técnico ou investigar um problema relatado.',
    'operacoes' => [
        [
            'nome' => 'Usuário',
            'como' => 'Digite o nome ou e-mail e escolha na lista.',
        ],
        [
            'nome' => 'Visualizar como',
            'como' => 'Somente leitura: nada do que você salvar durante a visualização é gravado de fato. Só Administrador, e não é possível visualizar como outro Administrador nem encadear uma visualização dentro de outra. Uma faixa fixa no topo da tela permite voltar para a sua própria conta a qualquer momento.',
        ],
    ],
    'conceitos' => [],
];
