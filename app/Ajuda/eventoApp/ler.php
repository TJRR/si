<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Conectar com participante',
    'resumo' => 'Leia o código do crachá de outra pessoa do evento para ficarem conectados. Uma leitura só já vale para as duas: quem lê e quem é lido ganham os pontos, e cada dupla conta uma única vez.',
    'operacoes' => [
        [
            'nome' => '"Usar a câmera"',
            'como' => 'Só aparece em navegadores com suporte à leitura automática de QR (Quick Response). Pede permissão de câmera e, ao apontar para o crachá da outra pessoa, registra sozinho. Em Safari e Firefox esse botão não aparece; a leitura é sempre pelo campo abaixo.',
        ],
        [
            'nome' => 'Campo de digitação manual',
            'como' => 'Digite o código de 6 caracteres mostrado no crachá da outra pessoa e toque em "Validar". Funciona em qualquer navegador, inclusive quando a leitura da câmera falha.',
        ],
        [
            'nome' => 'Resultado',
            'como' => 'Mostra a conexão registrada e quantos pontos você ganhou. Se vocês já estavam conectados, avisa isso e não pontua de novo. Ler o próprio código não vale e não conta como erro. Muitos códigos inexistentes em seguida bloqueiam novas tentativas por alguns minutos.',
            'observacao' => 'A conexão só pontua durante o evento, entre a data de início e a de fim, e o último dia conta inteiro.',
        ],
        [
            'nome' => 'Inscrição ainda não homologada',
            'como' => 'Em evento com credenciamento no local, as duas pessoas precisam ter passado pelo credenciamento antes de se conectar. A tela avisa qual das duas ainda falta.',
        ],
        [
            'nome' => '"Ver minhas conexões"',
            'como' => 'Abre a lista das pessoas com quem você já se conectou, com o contato que cada uma escolheu compartilhar.',
        ],
    ],
    'conceitos' => [],
];
