<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Quadro do Evento, novo/editar',
    'resumo' => 'Um quadro do carrossel da página pública deste evento.',
    'operacoes' => [
        [
            'nome' => 'Etiqueta acima do título',
            'como' => 'Selo curto em caixa alta, com cores próprias, no alto do quadro.',
        ],
        [
            'nome' => 'Segundo botão',
            'como' => 'Botão secundário, com contorno, ao lado do principal. Título sem endereço não é aceito.',
        ],
        ['nome' => 'Imagem de fundo', 'como' => 'Opcional: sem imagem, a cor de fundo prevalece. O sistema gera a versão para celular automaticamente.'],
        ['nome' => 'Efeito de camada sobre a imagem', 'como' => 'Só faz efeito com imagem de fundo cadastrada.'],
        ['nome' => 'Duração e efeito de transição', 'como' => 'Controlam quanto tempo este quadro fica visível e como o próximo aparece.'],
        ['nome' => 'Título', 'como' => 'Editor rico; permite colorir trechos do texto selecionando e aplicando a cor.'],
        ['nome' => 'Botão de ação', 'como' => 'Título e destino opcionais; informar um exige informar o outro.'],
    ],
    'conceitos' => [],
];
