<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Meu Perfil',
    'resumo' => 'Dados da sua própria conta, disponível para qualquer usuário autenticado.',
    'operacoes' => [
        [
            'nome' => 'Editar nome / Trocar foto',
            'como' => 'Foto até 4MB.',
        ],
        [
            'nome' => 'Dados complementares',
            'como' => 'Documento, cargo, categoria profissional, órgão de origem e minicurrículo. Usados quando você é designado facilitador de uma atividade (instrutor, professor, palestrante): preenchendo aqui, não precisa redigitar a cada nova designação.',
        ],
        [
            'nome' => 'Visualizar como outro usuário',
            'como' => 'Entra no sistema com a visão daquele usuário, para conferir o que ele vê.',
            'observacao' => 'Só Administrador, e não é possível visualizar como outro Administrador nem encadear uma visualização dentro de outra. Uma faixa fixa no topo da tela permite voltar para a sua própria conta a qualquer momento. "Parar visualização" não é uma tela própria: é esse elemento.',
        ],
    ],
    'conceitos' => [],
];
