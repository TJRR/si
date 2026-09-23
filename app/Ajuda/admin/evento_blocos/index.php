<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Blocos de conteúdo do Evento',
    'resumo' => 'Blocos de conteúdo livre da página pública deste evento. Diferente do Concurso, não existe bloco padrão fixo: todo bloco de um evento pode ser editado e removido livremente.',
    'operacoes' => [
        [
            'nome' => 'Posição e menu',
            'como' => 'A ordem do bloco na página, o liga e desliga e o atalho no menu do cabeçalho ficam em "Seções da página", junto com as demais seções do evento.',
        ],
        ['nome' => '+ Novo bloco', 'como' => 'Abre o formulário de um novo bloco deste evento.'],
        ['nome' => 'Editar / Remover', 'icone' => 'editar', 'como' => 'Remover também apaga o arquivo da imagem do bloco, se houver.'],
        ['nome' => 'Reordenar', 'como' => 'Ver conceito "Reordenar por arraste" abaixo: aqui define a ordem de exibição na página deste evento, sem afetar o Concurso nem outros eventos.'],
    ],
    'conceitos' => ['reordenar_arraste'],
];
