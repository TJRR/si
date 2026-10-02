<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Configurações da gamificação',
    'resumo' => 'O que o participante vê da pontuação no aplicativo, o extra de pontualidade, o texto das Regras do jogo e o encerramento da gincana.',
    'operacoes' => [
        [
            'nome' => 'Mostrar a pontuação no aplicativo',
            'como' => 'Ligado, o aplicativo mostra o total no painel e oferece "Minha pontuação" e "Regras do jogo" no menu. Desligado, nada disso aparece, e os pontos continuam sendo creditados pelos módulos de cada origem.',
        ],
        [
            'nome' => 'Mostrar aos inscritos os primeiros da classificação',
            'como' => 'Com a marca ligada, "Minha pontuação" mostra os primeiros, na quantidade informada. "Mostrar o nome" decide se a lista traz os nomes ou só posição e pontos. Cada inscrito sempre vê a própria posição.',
        ],
        [
            'nome' => 'Minutos de antecedência',
            'como' => 'Quem confirma presença até este número de minutos antes do início da atividade ganha o extra de pontualidade do tipo de atividade. Em branco, o extra não vale para ninguém.',
            'observacao' => 'A leitura só abre 15, 30 ou 60 minutos antes, conforme a atividade: a antecedência daqui precisa ser menor que essa abertura. Mudar os minutos vale só para as próximas presenças.',
        ],
        [
            'nome' => 'Texto de abertura',
            'como' => 'Aparece no topo das Regras do jogo. O resto da tela é montado a partir do cadastro de cada módulo.',
        ],
        [
            'nome' => 'Encerramento da gincana',
            'como' => 'Agende data e hora futuras, ou encerre agora digitando o nome do evento. Depois do encerramento nada mais pontua e a classificação fica congelada: presenças, visitas, conexões e credenciamentos continuam registrados, sem pontos; divulgação e participação em competições são recusadas; nenhuma anulação ou reversão move pontos.',
            'observacao' => 'Enquanto o instante agendado não chega, ele pode ser alterado ou retirado. Quando chega, é definitivo. Encerrar logo depois da apresentação do Prêmio deixa sem pontos a pesquisa respondida depois e as atividades da tarde.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin'],
];
