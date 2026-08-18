<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Trilhas',
    'resumo' => 'Categorias do concurso (ex.: "Interna"/"Externa"), com ordem, status e situação das inscrições.',
    'operacoes' => [
        [
            'nome' => '+ Nova trilha',
            'como' => 'Abre o formulário de uma nova trilha.',
        ],
        [
            'nome' => 'Formulários deste concurso',
            'como' => 'Atalho para a tela de Formulários dinâmicos do concurso (Administrador).',
        ],
        [
            'nome' => 'Abrir/fechar inscrições',
            'icone' => 'cadeado_fechado',
            'como' => 'Alterna se a página pública de inscrição desta trilha aceita novas equipes. Só Administrador.',
            'observacao' => 'Só funciona se a trilha já tiver uma etapa de ordem 1 chamada "Cadastro de Equipe" configurada — sem isso, o cadeado nem aparece.',
        ],
        [
            'nome' => 'Editar',
            'icone' => 'editar',
            'como' => 'Altera nome, descrição, ordem, mínimo de integrantes e se a trilha está ativa.',
        ],
        [
            'nome' => 'Remover',
            'icone' => 'remover',
            'como' => 'Apaga a trilha, com confirmação. Só Administrador.',
            'observacao' => 'Só funciona se a trilha ainda não tiver etapas, equipes, fórmula ou regras de desempate vinculados.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin'],
];
