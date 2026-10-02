<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Conexões do Evento',
    'resumo' => 'Acompanhamento das conexões entre participantes: quando alguém lê no aplicativo o código de outra pessoa (na tela do aplicativo dela ou no crachá), as duas ficam conectadas e as duas pontuam, com uma leitura só.',
    'operacoes' => [
        [
            'nome' => 'Conexões registradas',
            'como' => 'A lista mostra os dois participantes de cada par, os pontos de cada lado e quando se conectaram. Serve para conferir e corrigir uma leitura feita por engano.',
            'observacao' => 'Quem se conectou com quem é dado pessoal dos dois participantes: use a lista só para conferir e corrigir. Na tela de cada participante continua aparecendo apenas as conexões dele.',
        ],
        [
            'nome' => 'Filtros',
            'como' => 'Busca por nome ou correio eletrônico, que alcança as duas pessoas do par, e período da conexão. Clique no funil para aplicar e na seta para limpar.',
        ],
        [
            'nome' => 'Remover as selecionadas',
            'icone' => 'remover',
            'como' => 'Marque as linhas e remova: os pontos dos dois lados saem da classificação na hora, e as duas pessoas podem se conectar de novo. É o conserto de uma leitura feita por engano.',
            'observacao' => 'Depois do encerramento da gincana, nenhuma conexão pode ser removida: a classificação está congelada.',
        ],
        [
            'nome' => 'Exportar',
            'icone' => 'baixar',
            'como' => 'Baixa a lista filtrada em planilha de texto separada por ponto e vírgula, com as duas pessoas, os pontos de cada lado e o horário.',
        ],
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
