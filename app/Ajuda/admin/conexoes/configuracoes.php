<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Configurações de Conexões',
    'resumo' => 'Liga ou desliga as conexões entre participantes deste evento e define quanto cada uma vale.',
    'operacoes' => [
        [
            'nome' => 'Ativar as conexões neste evento',
            'como' => 'Desativado, o aplicativo não oferece a leitura de crachá entre participantes e nenhuma conexão nova é registrada. As já registradas continuam visíveis para quem as fez.',
        ],
        [
            'nome' => 'Pontos por conexão',
            'como' => 'Quanto cada uma das duas pessoas ganha quando uma lê o crachá da outra. Com zero, a conexão é registrada e entra na lista das duas, mas sem creditar ponto: serve para o evento que quer a lista de contatos sem a disputa de pontos.',
        ],
        [
            'nome' => 'Limite de conexões que pontuam por participante',
            'como' => 'Zero significa sem limite. Passado o limite, a pessoa continua podendo se conectar e a conexão continua sendo registrada, só que sem pontos, e a tela avisa isso a ela.',
        ],
        [
            'nome' => 'Quando a mudança vale',
            'como' => 'Pontos e limite valem só para as próximas conexões: o que já foi creditado não muda, nem para mais nem para menos.',
            'observacao' => 'As conexões só pontuam entre a data de início e a data de fim do evento, e o último dia conta inteiro.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin'],
];
