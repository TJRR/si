<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Conferir crachá',
    'resumo' => 'Leitura do código de credenciamento para a organização conferir quem está entrando. Mostra o nome, a foto (quando a pessoa cadastrou uma) e se a inscrição está homologada. Não grava nada: nem conexão, nem presença, nem credenciamento.',
    'operacoes' => [
        ['nome' => 'Usar a câmera', 'como' => 'Lê o código QR do crachá impresso ou da tela do aplicativo. Aparece só nos navegadores que leem código pela câmera; nos demais, use a digitação.'],
        ['nome' => 'Digitar o código', 'como' => 'Digite os 6 caracteres impressos abaixo do QR e clique em "Validar".'],
        ['nome' => 'Resultado', 'como' => 'Em verde, a inscrição está homologada. Em vermelho, a inscrição está pendente de homologação ou o código não pertence a nenhuma inscrição deste evento.'],
    ],
    'conceitos' => [],
];
