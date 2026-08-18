<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Etapas',
    'resumo' => 'Fases de uma trilha, cada uma com seu próprio mecanismo de avaliação, período, regra de transição e formulário vinculado.',
    'operacoes' => [
        [
            'nome' => '+ Nova etapa',
            'como' => 'Abre o formulário de dados gerais de uma nova etapa. Só Administrador.',
        ],
        [
            'nome' => 'Editar',
            'icone' => 'editar',
            'como' => 'Abre os dados gerais da etapa (ver ajuda da própria tela de edição para os detalhes de cada campo).',
        ],
        [
            'nome' => 'Remover',
            'icone' => 'remover',
            'como' => 'Apaga a etapa, com confirmação. Só Administrador.',
            'observacao' => 'Só funciona se a etapa ainda não tiver critérios, fórmula, avaliações ou submissões vinculados.',
        ],
        [
            'nome' => 'Ver formulário público',
            'como' => 'Só aparece quando o formulário vinculado está publicado — abre a mesma tela que a equipe vê.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin'],
];
