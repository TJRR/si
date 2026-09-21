<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Usuários',
    'resumo' => 'Aprovação de cadastros, atribuição de perfil/concurso, suspensão e reenvio de convite. Um usuário tem no máximo 1 perfil: aprovar ou editar sempre substitui o perfil anterior, nunca soma. O perfil "Inscrito em evento" é exceção: nasce auto-aprovado pelo fluxo público de um Evento, sem passar por esta tela; aparece aqui já com situação Aprovado, mostrando o(s) evento(s) em que a conta está inscrita no lugar do concurso (não tem relação com concurso nenhum). A coluna Perfis também mostra "Também inscrito em: ..." sempre que a conta tem histórico de inscrição em evento, mesmo depois de o perfil ser trocado para um perfil do Concurso. Essa informação nunca some ao editar o perfil. O selo laranja "Cadastro anterior pendente" aparece só no caso raro de uma conta que já estava pendente por outro motivo e foi auto-aprovada de passagem pelo evento. Use "Editar" para atribuir o perfil do Concurso que ela também precisa, se for o caso.',
    'operacoes' => [
        [
            'nome' => 'Filtros',
            'como' => 'Busca, concurso, perfil, situação (incluindo Suspenso), tipo de acesso.',
        ],
        [
            'nome' => 'Aprovar',
            'icone' => 'publicar',
            'como' => 'Inline, na própria linha: escolha perfil, concurso e (se for avaliador) categoria antes de confirmar.',
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
            'como' => 'Gera um novo hiperlink de definir senha, invalidando o anterior.',
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
