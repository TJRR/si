<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Tema',
    'resumo' => 'Gestão dos temas de cor do sistema (vale para todas as edições, não é por concurso): cada usuário escolhe, em "Meu Perfil", qual tema publicado usar. Também é aqui que fica o favicon.',
    'operacoes' => [
        [
            'nome' => 'Novo tema',
            'como' => 'Nasce privado (nenhum usuário consegue escolher) até ser publicado.',
        ],
        [
            'nome' => 'Duplicar',
            'como' => 'Cria uma cópia editável a partir de qualquer tema, inclusive os 5 temas de sistema. Ajuste as cores e publique quando estiver pronto.',
        ],
        [
            'nome' => 'Publicar / Despublicar',
            'como' => 'Só temas publicados aparecem para o usuário escolher. O tema marcado como padrão não pode ser despublicado sem antes definir outro como padrão.',
        ],
        [
            'nome' => 'Definir como padrão',
            'como' => 'Tema que qualquer usuário sem escolha própria vê. Só é possível definir um tema publicado.',
        ],
        [
            'nome' => 'Remover',
            'como' => 'Só temas customizados que não sejam o padrão atual. Quem usava o tema removido volta automaticamente para o tema padrão do sistema. Os 5 temas de sistema não podem ser removidos nem editados.',
        ],
        [
            'nome' => 'Favicon',
            'como' => 'Ícone exibido na aba do navegador, único para o sistema inteiro (não faz parte de tema).',
        ],
    ],
    'conceitos' => [],
];
