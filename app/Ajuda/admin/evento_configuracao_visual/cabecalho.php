<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Cabeçalho do Evento',
    'resumo' => 'Aparência do cabeçalho da página pública deste evento, mais o controle de quando essa página fica disponível.',
    'operacoes' => [
        [
            'nome' => 'Logo do evento',
            'como' => 'Enviada aqui, vale na página pública deste evento e no rodapé dela, sem depender do tema de cor escolhido por quem visita. Sem logo enviada, continua valendo a do tema.',
        ],
        [
            'nome' => 'Fontes da página pública',
            'como' => 'Escolha a fonte dos títulos e a fonte do texto da página pública deste evento, numa lista fechada. Sem escolha, a página usa as fontes padrão do sistema.',
            'observacao' => 'O tempo e o efeito de troca do carrossel ficam em cada quadro, na sub-aba Carrossel, exatamente como na página inicial do Concurso.',
        ],
        [
            'nome' => 'Publicar esta página',
            'como' => 'Enquanto não estiver marcada, o endereço público deste evento responde como não encontrado para qualquer pessoa, mesmo já sabendo o endereço.',
            'observacao' => 'Marcada, a tela mostra o endereço completo, pronto para divulgar.',
        ],
        [
            'nome' => 'Imagem de fundo',
            'como' => '1920×800, opcional.',
            'observacao' => 'Vários outros campos desta tela (posição, transição, opacidade, efeito de entrada, logo clara) só têm efeito visível se houver imagem de fundo cadastrada.',
        ],
        ['nome' => 'Posição (grade 3×3)', 'como' => 'Ponto de enquadramento da imagem de fundo.'],
        ['nome' => 'Efeito de transição na base', 'como' => 'Onda ou Diagonal: a forma que separa o cabeçalho do restante da página.'],
        ['nome' => 'Logo clara alternativa', 'como' => 'Só é usada quando há imagem de fundo; sem ela, a logo normal do evento continua valendo mesmo sobre a imagem.'],
        ['nome' => 'Título/subtítulo', 'como' => 'Editor rico, só aparece com imagem de fundo cadastrada.'],
    ],
    'conceitos' => [],
];
