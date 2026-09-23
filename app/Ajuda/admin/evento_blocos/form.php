<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Bloco de conteúdo do Evento, editar',
    'resumo' => 'Um bloco de conteúdo livre da página pública deste evento.',
    'operacoes' => [
        [
            'nome' => 'Posição e menu',
            'como' => 'A ordem do bloco na página, o liga e desliga e o atalho no menu do cabeçalho ficam em "Seções da página", junto com as demais seções do evento.',
        ],
        ['nome' => 'Âncora da seção', 'como' => 'Usada no menu e na navegação por rolagem; sem espaços.'],
        ['nome' => 'Conteúdo', 'como' => 'Editor rico.'],
        ['nome' => 'Imagem', 'como' => 'Opcional, com posição configurável em relação ao texto.'],
        ['nome' => 'Botão de ação', 'como' => 'Título, hiperlink e alinhamento, todos opcionais.'],
        ['nome' => 'Adicionar atalho no menu superior', 'como' => 'Opt-in por bloco: sem marcar, o bloco não vira item de menu, mesmo estando ativo.'],
    ],
    'conceitos' => [],
];
