<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Evento: Divulgação na Página Inicial',
    'resumo' => 'Bloco de chamada deste evento, exibido na página inicial pública: título, conteúdo (editor rico), imagem opcional e botão de chamada. Fica em posição fixa, entre o carrossel de imagens e as Faixas, fora do mecanismo de ordenação por arraste. O botão sempre leva direto à inscrição deste evento, sem hiperlink configurável. Só aparece na página inicial se estiver "Ativo" e com título preenchido. Se mais de um evento estiver com divulgação ativa ao mesmo tempo, todos aparecem empilhados, com o mais recente primeiro.',
    'operacoes' => [
        ['nome' => 'Salvar', 'como' => 'Grava o bloco de divulgação deste evento (cria na primeira vez, atualiza nas seguintes).'],
    ],
    'conceitos' => [],
];
