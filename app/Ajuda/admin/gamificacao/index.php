<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Gamificação: classificação geral',
    'resumo' => 'A soma, na hora, de todos os pontos válidos de cada inscrito: presença em atividades, competições, conexões, visitas a estandes, divulgação e bônus (que incluem as ações do participante).',
    'operacoes' => [
        [
            'nome' => 'Busca',
            'como' => 'Reduz a lista a quem casa com o nome ou o correio eletrônico digitado. A posição mostrada continua sendo a do evento inteiro, nunca a da lista reduzida.',
        ],
        [
            'nome' => 'Exportar a classificação',
            'icone' => 'baixar',
            'como' => 'Baixa a lista atual em planilha de texto separada por ponto e vírgula, com posição, empate, nome, total e os pontos de cada origem. Com busca aplicada, exporta só o que está na tela.',
        ],
        [
            'nome' => 'Selos do topo',
            'como' => 'Mostram se a pontuação está ligada no aplicativo, o que os inscritos veem da classificação e se a gincana tem encerramento agendado ou já foi encerrada.',
        ],
        [
            'nome' => 'Lista',
            'como' => 'Posição, nome, total e o total de cada origem. Só aparece quem tem ao menos um ponto. Empate no total é resolvido pela cascata da aba Desempate; esgotada a cascata, a posição é compartilhada e a linha recebe a marca "Empate".',
        ],
        [
            'nome' => 'Extrato',
            'como' => 'Abre o detalhe dos pontos de uma pessoa, origem por origem, com as anulações e os motivos. Conexões aparecem só em total.',
        ],
        [
            'nome' => 'Reconferir agora',
            'como' => 'Cria o crédito que falta para presenças já registradas (pelo horário gravado da leitura) e para ações do participante feitas antes de o bônus existir. Use depois de cadastrar pontos num tipo de atividade ou um bônus novo.',
            'observacao' => 'Faça as reconferências antes do evento: um crédito criado por reconferência leva o instante dela, e isso conta no critério de desempate "quem chegou primeiro". Depois do encerramento, o botão some.',
        ],
        [
            'nome' => 'Exportar a classificação',
            'como' => 'Planilha de texto separada por ponto e vírgula, com posição, empate, nome, correio eletrônico, total e cada origem. É a lista para a entrega de prêmios aos primeiros.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin'],
];
