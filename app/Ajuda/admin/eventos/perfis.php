<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Evento: Perfis',
    'resumo' => 'Perfis da equipe de organização deste evento (Instrutor, Professor, Palestrante, ou outro nome que preferir). Usados ao vincular um facilitador a uma atividade, na sub-aba Facilitadores.',
    'operacoes' => [
        [
            'nome' => 'Cadastrar',
            'como' => 'Informe só o nome do perfil. A lista é própria deste evento: cada edição pode ter perfis diferentes.',
        ],
        [
            'nome' => 'Remover',
            'como' => 'Só é possível remover um perfil que ainda não está em uso por nenhum facilitador. Se estiver em uso, o sistema recusa e mantém o perfil.',
        ],
    ],
    'conceitos' => [],
];
