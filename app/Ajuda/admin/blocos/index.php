<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Blocos de Conteúdo',
    'resumo' => 'Seções de texto + imagem da home — os blocos padrão ("Sobre o Prêmio", "Premiação") sempre existem; blocos livres são criados pelo Administrador.',
    'operacoes' => [
        [
            'nome' => '+ Novo (livre)',
            'como' => 'Cria um bloco novo, além dos padrão.',
        ],
        [
            'nome' => 'Editar',
            'icone' => 'editar',
            'como' => 'Título, âncora, conteúdo (editor rico), imagem, botão opcional, Ativo, "Adicionar no menu superior" e "Mostrar no rodapé".',
            'observacao' => 'Nos blocos padrão, a âncora vem travada (não pode ser editada) e "Adicionar no menu superior" não está disponível — só nos blocos livres.',
        ],
        [
            'nome' => 'Remover',
            'icone' => 'remover',
            'como' => 'Apaga o bloco.',
            'observacao' => 'Só disponível para blocos livres — os padrão não podem ser removidos, só (des)ativados.',
        ],
        [
            'nome' => 'Reordenar',
            'como' => 'Ver conceito "Reordenar por arraste" abaixo.',
        ],
    ],
    'conceitos' => ['reordenar_arraste', 'nunca_apaga_so_versiona'],
];
