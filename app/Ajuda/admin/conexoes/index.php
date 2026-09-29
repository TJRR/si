<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Conexões do Evento',
    'resumo' => 'Acompanhamento das conexões entre participantes: quando alguém lê o crachá de outra pessoa no aplicativo, as duas ficam conectadas e as duas pontuam, com uma leitura só.',
    'operacoes' => [
        [
            'nome' => 'Situação e regras do evento',
            'como' => 'Mostra se as conexões estão ativadas, quantos pontos cada uma vale, se há limite por participante e entre quais datas elas pontuam.',
        ],
        [
            'nome' => 'Números do evento',
            'como' => 'Total de conexões registradas, quantas pessoas já se conectaram ao menos uma vez e o total de pontos creditados.',
            'observacao' => 'Não há lista de quem se conectou com quem: esse par é dado pessoal de cada participante e aparece só na tela dele.',
        ],
        [
            'nome' => 'Conexões por dia',
            'como' => 'Quantas conexões aconteceram em cada dia do evento, para acompanhar o movimento.',
        ],
        [
            'nome' => '"Configurações"',
            'como' => 'Ativa ou desativa as conexões e define os pontos e o limite por participante.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin'],
];
