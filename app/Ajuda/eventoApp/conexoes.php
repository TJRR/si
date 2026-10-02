<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Minhas conexões',
    'resumo' => 'Pessoas do evento com quem você já se conectou, e os pontos ganhos. A conexão acontece quando uma das duas lê o código da outra, na tela do aplicativo ou no crachá: uma leitura só vale para as duas.',
    'operacoes' => [
        [
            'nome' => '"Conectar com participante"',
            'como' => 'Abre o leitor do código do participante (câmera, quando o navegador permitir, ou digitação) e mostra também o seu próprio código.',
        ],
        [
            'nome' => 'Seus pontos em conexões',
            'como' => 'Soma dos pontos já creditados a você. Quando o evento tem limite de conexões que pontuam, mostra também quantas das suas já pontuaram.',
        ],
        [
            'nome' => 'Dados de cada pessoa',
            'como' => 'Nome e endereço de correio eletrônico aparecem sempre. Foto, cargo, órgão, minicurrículo, telefone e redes sociais só aparecem quando a própria pessoa autoriza, em Meu Perfil.',
            'observacao' => 'Cada um controla o que mostra a qualquer momento, e a mudança vale também para as conexões já feitas. Esconder depois vale para o sistema, não para a memória de quem já viu.',
        ],
        [
            'nome' => 'Falar com a pessoa',
            'como' => 'O endereço de correio eletrônico abre o seu programa de mensagens; o telefone abre o discador do aparelho; o telefone marcado como WhatsApp abre a conversa; cada rede social abre o perfil que a pessoa cadastrou.',
        ],
    ],
    'conceitos' => [],
];
