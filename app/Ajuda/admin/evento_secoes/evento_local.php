<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Local e acesso',
    'resumo' => 'Endereço do evento, texto de apoio e mapa.',
    'operacoes' => [
        ['nome' => 'Endereço de incorporação do mapa', 'como' => 'Aceita apenas endereços do Google Maps ou do OpenStreetMap. Preenchido, a página passa a carregar o mapa desses serviços, que recebem o endereço de rede de quem visita.'],
        ['nome' => 'Imagem do local', 'como' => 'Usada quando não há mapa incorporado: nada de fora é carregado junto com a página.'],
        ['nome' => 'Como obter o endereço de incorporação', 'como' => 'No Google Maps, abra o local, clique em "Compartilhar" e depois em "Incorporar um mapa". Copie o código mostrado e cole inteiro no campo: o sistema guarda só o endereço do mapa. O endereço curto de compartilhamento (maps.app.goo.gl) não serve para incorporar.'],
        ['nome' => 'Abrir no mapa', 'como' => 'Endereço opcional do botão, para abrir o local no aplicativo de mapas de quem visita. Em branco, o botão usa o endereço do mapa cadastrado em Configurações, Contato (o mesmo do rodapé).'],
        ['nome' => 'Disposição', 'como' => 'Texto à esquerda e mapa (ou imagem) num quadro branco à direita. Sem mapa nem imagem, o texto ocupa a largura toda.'],
    ],
    'conceitos' => [],
];
