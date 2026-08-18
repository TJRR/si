<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Página inicial',
    'resumo' => 'Página institucional do concurso ativo — conteúdo e ordem das seções são configurados pelo Administrador; esta ajuda cobre só o que você, como visitante, pode fazer nesta tela.',
    'operacoes' => [
        [
            'nome' => 'Menu superior',
            'como' => 'Leva direto a cada seção da página. Some sozinho se a seção correspondente não estiver ativa.',
        ],
        [
            'nome' => 'Ícone de calendário',
            'como' => 'Abre um painel lateral com a linha do tempo (cronograma) do concurso, sem precisar rolar a página.',
        ],
        [
            'nome' => 'Slideshow (topo)',
            'como' => 'Use as setas ou os marcadores abaixo das imagens para navegar manualmente — ele também avança sozinho.',
        ],
        [
            'nome' => '"Ver equipes homologadas" / "Ver resultado" (por trilha)',
            'como' => 'Só aparecem depois que o Administrador publica a homologação/resultado daquela trilha ou etapa. Levam a páginas públicas, sem necessidade de login.',
        ],
        [
            'nome' => 'Desafios',
            'como' => 'Use as abas para trocar de trilha e a busca para filtrar por texto; clique num cartão para expandir a pergunta completa.',
        ],
        [
            'nome' => 'Linha do tempo (seção Cronograma)',
            'como' => 'Cada item mostra uma etiqueta de status, calculada automaticamente pela data — sem ação do Administrador.',
            'pills' => [
                ['cor' => 'azul', 'rotulo' => 'Futuro'],
                ['cor' => 'laranja', 'rotulo' => 'Em andamento'],
                ['cor' => 'verde', 'rotulo' => 'Concluído'],
            ],
        ],
        [
            'nome' => 'FAQ',
            'como' => 'Clique numa pergunta para expandir a resposta (acordeão).',
        ],
        [
            'nome' => '"Inscreva-se" (por trilha)',
            'como' => 'Leva ao formulário público de inscrição de uma nova equipe — não requer login prévio, é o próprio formulário que cria a conta.',
            'observacao' => 'Só fica disponível enquanto as inscrições daquela trilha estiverem abertas.',
        ],
        [
            'nome' => 'Fale conosco (rodapé)',
            'como' => 'Formulário nativo de contato, quando ativado pelo Administrador — envia uma mensagem para a equipe organizadora.',
        ],
    ],
    'conceitos' => [],
];
