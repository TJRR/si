<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Participações em competições',
    'resumo' => 'Quem participou de cada competição, com os pontos creditados, e as ações de anular e desfazer a anulação.',
    'operacoes' => [
        [
            'nome' => 'Filtros',
            'como' => 'Busca por nome ou correio eletrônico, competição, situação e período da participação, todos combináveis. Clique no funil para aplicar e na seta para limpar.',
            'observacao' => 'O número de linhas encontradas e a exportação acompanham o filtro escolhido.',
        ],
        [
            'nome' => 'Marcar linhas',
            'como' => 'A caixa do cabeçalho marca e desmarca todas as linhas. As operações em lote agem só sobre o que estiver marcado, e o envio sem nada marcado é recusado com aviso.',
        ],
        [
            'nome' => 'Anular',
            'como' => 'Exige um motivo, que a pessoa lê no aviso do sino. Os pontos saem de toda soma na hora. Serve, por exemplo, para quem leu o código de uma foto sem ter participado.',
            'observacao' => 'A pessoa não consegue registrar a participação de novo na mesma competição: o conserto de um engano é desfazer a anulação.',
        ],
        [
            'nome' => 'Desfazer anulação',
            'como' => 'Devolve os pontos e avisa a pessoa.',
            'observacao' => 'Depois do encerramento da gincana, nenhuma participação pode ser anulada ou restabelecida.',
        ],
        [
            'nome' => 'Anular e desfazer em lote',
            'como' => 'Marque as linhas, escreva um motivo e use "Anular": ele vale para todas as selecionadas, e cada pessoa recebe o aviso no sino. "Desfazer anulação" devolve os pontos das selecionadas.',
        ],
        [
            'nome' => 'Exportar',
            'icone' => 'baixar',
            'como' => 'Baixa a lista filtrada em planilha de texto separada por ponto e vírgula, com competição, nome, correio eletrônico, pontos, horário e situação.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin'],
];
