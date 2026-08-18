<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Concursos',
    'resumo' => 'É a raiz do sistema: cada linha é uma edição do Prêmio de Inovação (ex.: "5º Prêmio de Inovação"). É a partir daqui que se chega às Trilhas, Etapas e a tudo o mais de uma edição.',
    'operacoes' => [
        [
            'nome' => '+ Novo concurso',
            'como' => 'Abre o formulário de uma nova edição (nome, descrição, período, status). Só aparece para Administrador global.',
        ],
        [
            'nome' => 'Trilhas',
            'como' => 'Abre a lista de trilhas (categorias, ex.: Interna/Externa) daquele concurso.',
        ],
        [
            'nome' => 'Editar',
            'icone' => 'editar',
            'como' => 'Altera nome, descrição, período e status do concurso. Só Administrador.',
        ],
        [
            'nome' => 'Remover',
            'icone' => 'remover',
            'como' => 'Apaga o concurso, com confirmação. Só Administrador.',
            'observacao' => 'Só funciona se o concurso ainda não tiver trilhas, formulários ou categorias de avaliador vinculados — remova essas dependências primeiro.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin'],
];
