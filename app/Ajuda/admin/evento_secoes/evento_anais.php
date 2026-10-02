<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Seção Anais',
    'resumo' => 'Mostra na página pública do evento o botão para baixar a versão publicada dos Anais e, se marcado, a relação dos trabalhos selecionados.',
    'operacoes' => [
        ['nome' => 'Etiqueta, título e texto de apoio', 'como' => 'Cabeçalho da seção na página.'],
        ['nome' => 'Cores', 'como' => 'Cor de fundo e cor do texto da seção, em formato #rrggbb.'],
        ['nome' => 'Mostrar a relação dos trabalhos selecionados', 'como' => 'Lista, agrupada por eixo temático e em ordem alfabética, os trabalhos selecionados no resultado de Trabalhos, com título e autoria. Nunca mostra nota, posição nem os trabalhos que não foram selecionados.', 'observacao' => 'A relação só aparece depois de o resultado de Trabalhos ser publicado. Antes disso, a seção mostra só o restante.'],
        ['nome' => 'Botão de baixar os Anais', 'como' => 'Aparece sozinho quando há uma versão publicada do volume (Trabalhos, Anais), e leva sempre à versão vigente.'],
        ['nome' => 'Ir para os Anais', 'como' => 'Abre a tela em que o volume é enviado e publicado.'],
    ],
    'conceitos' => [],
];
