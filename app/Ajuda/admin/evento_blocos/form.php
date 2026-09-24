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
        ['nome' => 'Etiqueta acima do título', 'como' => 'Texto curto em caixa alta, com cor própria, por exemplo "GARANTA SUA VAGA".'],
        ['nome' => 'Usar a cor de fundo do rodapé', 'como' => 'O bloco passa a ter exatamente a cor do rodapé e encosta nele, como uma continuação. Pensado para a chamada final, logo antes do rodapé.'],
        ['nome' => 'Botões de ação', 'como' => 'Até dois, cada um com título, destino e cores próprias, todos opcionais. Sem cor de fundo, o segundo botão fica só com contorno.'],
        ['nome' => 'Destinos aceitos', 'como' => 'Âncora da própria página ("#programacao"), endereço completo ("https://...") ou endereço interno do sistema ("eventoInscricao/index/1"), que o sistema completa sozinho.'],
    ],
    'conceitos' => [],
];
