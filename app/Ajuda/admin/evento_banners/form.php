<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Faixa do Evento, nova/editar',
    'resumo' => 'Uma faixa da página pública deste evento.',
    'operacoes' => [
        ['nome' => 'Imagem de fundo', 'como' => 'Opcional: sem imagem, a cor de fundo prevalece.'],
        ['nome' => 'Texto sobreposto', 'como' => 'Editor rico, com alinhamento configurável.'],
        ['nome' => 'Cor do texto', 'como' => 'Sem cor escolhida, o texto sai branco; sobre fundo claro, escolha uma cor escura.'],
        ['nome' => 'Formato', 'como' => 'Retângulo é a faixa de borda a borda. Balão de diálogo desenha o primeiro parágrafo do texto como o balão da identidade visual (borda escura, ponta saindo do canto superior direito e sombra escura sólida, na cor de fundo escolhida), sobre fundo branco, e os demais parágrafos como uma linha discreta abaixo dele. Use uma cor que contraste com o carrossel logo acima.'],
        ['nome' => 'Botão de ação', 'como' => 'Destino pode ser um hiperlink interno, externo, uma âncora da própria página, um arquivo ou um vídeo.'],
    ],
    'conceitos' => [],
];
