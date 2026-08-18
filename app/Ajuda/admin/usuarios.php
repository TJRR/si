<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Usuários',
    'resumo' => 'Aprovação de cadastros, atribuição de perfil/concurso, suspensão e reenvio de convite. Um usuário tem no máximo 1 perfil — aprovar ou editar sempre substitui o perfil anterior, nunca soma.',
    'operacoes' => [
        [
            'nome' => 'Filtros',
            'como' => 'Busca, concurso, perfil, status (incluindo Suspenso), tipo de acesso.',
        ],
        [
            'nome' => 'Aprovar',
            'icone' => 'publicar',
            'como' => 'Inline, na própria linha — escolha perfil, concurso e (se for avaliador) categoria antes de confirmar.',
        ],
        [
            'nome' => 'Rejeitar',
            'icone' => 'rejeitar',
            'como' => 'Inline, na própria linha.',
        ],
        [
            'nome' => 'Reverter rejeição',
            'como' => 'Volta o cadastro para "pendente", com confirmação.',
        ],
        [
            'nome' => 'Editar',
            'icone' => 'editar',
            'como' => 'Abre a tela de edição completa do usuário.',
        ],
        [
            'nome' => 'Reenviar convite',
            'como' => 'Gera um novo link de definir senha, invalidando o anterior.',
            'observacao' => 'Só aparece para quem nunca acessou o sistema (convite ainda não usado).',
        ],
        [
            'nome' => 'Suspender / Reativar',
            'como' => 'Suspender bloqueia login imediatamente, com confirmação.',
        ],
        [
            'nome' => '+ Convidar usuário',
            'como' => 'Cria um acesso direto, sem passar por autocadastro pendente.',
        ],
    ],
    'conceitos' => [],
];
