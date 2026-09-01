<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Bloco da Edição',
    'resumo' => 'Conteúdo em texto rico opcional, específico desta edição, exibido na página pública "Edições Anteriores" logo depois da galeria de fotos — útil para informações que não se encaixam no modelo genérico de trilhas/vencedores, como uma "Classificação Geral" do encontro.',
    'operacoes' => [
        [
            'nome' => 'Título da seção',
            'como' => 'Opcional. Aparece como cabeçalho acima do conteúdo na página pública; deixe em branco para exibir só o conteúdo, sem título.',
        ],
        [
            'nome' => 'Conteúdo',
            'como' => 'Editor rico — mesmo editor usado nos Blocos de Conteúdo da home e no Contato.',
        ],
        [
            'nome' => 'Exibir este bloco na página pública desta edição',
            'como' => 'Checkbox — desmarcar oculta o bloco da página pública sem apagar o texto salvo, permitindo reativar depois. Só concursos com status "Encerrado" têm página pública ("Edições Anteriores"); em um concurso ainda ativo, o bloco fica salvo mas não aparece em lugar nenhum até o concurso ser encerrado.',
        ],
        [
            'nome' => 'Salvar',
            'como' => 'Há no máximo 1 bloco por concurso — salvar sempre atualiza o mesmo registro desta edição. Só Administrador grava; Suporte só visualiza.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin'],
];
