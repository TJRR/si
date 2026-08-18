<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Formulários',
    'resumo' => 'Formulários dinâmicos (de inscrição de equipe ou de submissão por etapa), com versão e ciclo de vida. Só Administrador tem acesso a esta tela.',
    'operacoes' => [
        [
            'nome' => '+ Novo',
            'como' => 'Cria um formulário vazio e já leva para a tela de Campos.',
        ],
        [
            'nome' => 'Editar',
            'icone' => 'editar',
            'como' => 'Altera nome/descrição do formulário (não os campos).',
        ],
        [
            'nome' => 'Publicar / Despublicar / Arquivar / Desarquivar',
            'icone' => 'publicar',
            'como' => 'Avança o formulário pelo ciclo de vida: rascunho → publicado → despublicado → arquivado. Desarquivar volta para despublicado (não direto para publicado).',
        ],
        [
            'nome' => 'Campos',
            'como' => 'Abre a lista de campos deste formulário.',
        ],
        [
            'nome' => 'Duplicar',
            'como' => 'Cria uma nova versão editável a partir de um formulário publicado ou despublicado.',
            'observacao' => 'Só disponível para formulário publicado ou despublicado — é o único jeito de alterar campos depois que o formulário já saiu do rascunho.',
        ],
        [
            'nome' => 'Remover',
            'icone' => 'remover',
            'como' => 'Apaga o formulário, com confirmação.',
            'observacao' => 'Só funciona se ele ainda não tiver campos, etapas vinculadas ou submissões.',
        ],
    ],
    'conceitos' => ['nunca_apaga_so_versiona'],
];
