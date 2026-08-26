<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'FAQ (banco global)',
    'resumo' => 'Banco global de perguntas frequentes, reaproveitável entre todas as edições do Prêmio — cadastrar aqui não faz a pergunta aparecer em nenhuma home sozinha, é preciso ativá-la em "FAQ desta edição". Aberto a Administrador e Suporte, mas só com perfil global: como o banco vale para todas as edições, quem está vinculado a um concurso específico administra o FAQ da própria edição, não este.',
    'operacoes' => [
        [
            'nome' => '+ Nova / Editar / Remover',
            'icone' => 'editar',
            'como' => 'CRUD da pergunta/resposta.',
            'observacao' => 'Remover só funciona se a pergunta não estiver ativa em nenhuma edição no momento.',
        ],
        [
            'nome' => 'Perguntas vindas de dúvidas reais',
            'como' => 'Parte das perguntas daqui não foi escrita do zero: nasceu de uma dúvida real de participante, promovida pelo Administrador/Suporte na tela de atendimento da dúvida. O texto foi reescrito em termos genéricos na promoção e o item não guarda vínculo visível com a equipe que perguntou.',
            'observacao' => 'Editar ou remover a pergunta aqui não afeta a dúvida de origem, que continua intacta e exclusiva da equipe.',
        ],
    ],
    'conceitos' => ['banco_global_vs_edicao'],
];
