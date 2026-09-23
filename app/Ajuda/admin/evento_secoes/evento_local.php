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
        ['nome' => 'Abrir no mapa', 'como' => 'Endereço opcional do botão, para abrir o local no aplicativo de mapas de quem visita.'],
    ],
    'conceitos' => [],
];
