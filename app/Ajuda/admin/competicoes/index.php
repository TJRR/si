<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Competições',
    'resumo' => 'Competições e experiências em que o participante pontua por participar, como cantar no karaokê ou competir na Batalha de Prompts. Não há inscrição prévia nem vencedor: quem participa lê o código que o responsável mostra na hora.',
    'operacoes' => [
        [
            'nome' => 'Nova competição',
            'como' => 'Nome, atividade da programação em que acontece, pontos por participação, regras e se está ativa. O código de participação é gerado na hora e não muda depois.',
        ],
        [
            'nome' => 'Imprimir o cartão do código',
            'como' => 'Cartão A4 com o QR (Quick Response) e o código, para o responsável levar na mão. Quem é facilitador da atividade ligada também vê o código em tela cheia no aplicativo, em "Minhas facilitações".',
        ],
        [
            'nome' => 'Quem pontua e quando',
            'como' => 'Cada pessoa pontua uma vez por competição. Com atividade ligada, a leitura vale da abertura da leitura da atividade até o fim dela; sem atividade, nos dias do evento. Quem só assiste pontua pela presença na atividade, pelo código afixado no espaço.',
            'observacao' => 'O facilitador da atividade ligada não pontua na competição que conduz. Depois do encerramento da gincana, a participação é recusada.',
        ],
        [
            'nome' => 'Remover',
            'icone' => 'remover',
            'como' => 'Competição sem participação é apagada. Com participação, é desativada: o código deixa de valer e os pontos já dados continuam.',
        ],
        [
            'nome' => 'Arrastar',
            'como' => 'Define a ordem das competições nas Regras do jogo.',
        ],
    ],
    'conceitos' => ['reordenar_arraste', 'permissao_suporte_admin'],
];
