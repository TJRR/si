<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Pesquisa de satisfação',
    'resumo' => 'Conte à organização o que você achou do evento. Quando há bônus cadastrado para isso, responder também vale pontos.',
    'operacoes' => [
        [
            'nome' => 'As suas respostas ficam separadas do seu nome',
            'como' => 'A organização vê que você respondeu, para creditar os pontos, e vê o conjunto das respostas, mas não consegue ligar uma coisa à outra pelo sistema. Nenhuma tela mostra o que uma pessoa específica respondeu.',
        ],
        [
            'nome' => 'Responder',
            'como' => 'Preencha as perguntas e toque em "Enviar respostas". As perguntas marcadas com asterisco são obrigatórias.',
        ],
        [
            'nome' => 'Uma vez só',
            'como' => 'A pesquisa é respondida uma única vez, e as respostas não podem ser alteradas depois: como elas não ficam ligadas ao seu nome, nem o sistema consegue encontrar as suas para trocar.',
        ],
        [
            'nome' => 'Quando ela fica aberta',
            'como' => 'A organização define o período. Fora dele, a tela informa as datas e não aceita resposta.',
        ],
        [
            'nome' => 'Pontos',
            'como' => 'Entram assim que você envia, e aparecem no painel junto com os demais bônus.',
        ],
    ],
    'conceitos' => ['pesquisa_anonima'],
];
