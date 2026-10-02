<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Meu estande',
    'resumo' => 'Painel de quem representa um estande do evento: visitas registradas, cartaz com o código e os dados que os participantes veem.',
    'operacoes' => [
        ['nome' => 'Visitas', 'como' => 'Quantas visitas o estande recebeu pela leitura do código no aplicativo do evento. Os nomes de quem visitou não aparecem.'],
        ['nome' => 'Abrir o cartaz para imprimir', 'como' => 'Cartaz A4 com o QR e o código do estande. Deixe-o à vista no estande.'],
        ['nome' => 'Mostrar o código em tela cheia', 'como' => 'Abre o QR do estande em tela cheia com fundo branco, para o visitante ler direto do seu celular ou computador. Toque em qualquer lugar para fechar. Você escolhe como apresentar o código: cartaz, tela ou os dois.'],
        ['nome' => 'Atualizar nome, descrição e logotipo', 'como' => 'O que você salvar aparece na hora no aplicativo e na página do evento.'],
        ['nome' => 'Responder à pesquisa', 'como' => 'Aparece quando a pesquisa de satisfação do evento está aberta. Você responde uma vez; as respostas são anônimas. Quem não é inscrito no evento responde sem receber pontos.'],
    ],
    'conceitos' => [],
];
