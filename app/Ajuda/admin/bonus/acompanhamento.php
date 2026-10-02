<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Acompanhamento dos bônus',
    'resumo' => 'Quem ganhou cada bônus, quantos pontos foram concedidos, e as ações de anular e desfazer a anulação.',
    'operacoes' => [
        [
            'nome' => 'Reconferir agora',
            'como' => 'Refaz a conferência de todos os inscritos e concede o que estiver vencido. A conferência já acontece sozinha a cada presença confirmada e a cada alteração no cadastro dos bônus; este botão serve para o que não passa por nenhuma das duas, como a data de uma atividade corrigida depois do evento.',
            'observacao' => 'Depois do encerramento da gincana (Gamificação, Configurações), a classificação fica congelada: o botão some, e nenhum crédito pode ser anulado, restabelecido ou cancelado em lote.',
        ],
        [
            'nome' => 'Exigência atingida',
            'como' => 'Ao lado de cada crédito aparece o número que o bônus exigia no momento da concessão. É o que explica por que aquela pessoa recebeu, mesmo depois de a exigência ter mudado.',
        ],
        [
            'nome' => 'Anular um crédito',
            'como' => 'Exige um motivo, que é mostrado ao participante no painel dele e enviado pelo sino de avisos. Os pontos saem de toda soma na hora.',
            'observacao' => 'Diferente da Divulgação: o crédito anulado por uma pessoa não volta sozinho. Nenhuma presença nova o concede de novo, e o único caminho de volta é desfazer a anulação.',
        ],
        [
            'nome' => 'Crédito "anulado pelo sistema"',
            'como' => 'Aparece quando a organização remove uma presença registrada por engano e o bônus daquela pessoa perde a quantidade exigida. O sistema anula na hora e avisa ela.',
            'observacao' => 'Este tipo volta sozinho: se a presença for registrada de novo, o crédito é restabelecido sem ninguém precisar fazer nada.',
        ],
        [
            'nome' => 'Cancelar todos os créditos de um bônus',
            'como' => 'Para o caso de um bônus cadastrado errado ter concedido pontos a quem não devia. Exige o motivo e o nome exato do bônus digitado de novo, e avisa cada pessoa atingida pelo sino.',
            'observacao' => 'Com muitos créditos a operação demora alguns segundos, porque cada aviso é gravado um a um. "Desfazer o cancelamento deste bônus" restabelece o que foi cancelado assim, e não alcança o que o sistema anulou por falta de presença.',
        ],
        [
            'nome' => 'Desfazer a anulação',
            'como' => 'Restabelece os pontos e avisa a pessoa. É o conserto de uma anulação feita por engano.',
        ],
        [
            'nome' => 'Filtros',
            'como' => 'Busca por nome ou correio eletrônico, bônus, situação e período do crédito, todos combináveis. Clique no funil para aplicar e na seta para limpar.',
            'observacao' => 'O número de créditos encontrados e a exportação acompanham o filtro escolhido.',
        ],
        [
            'nome' => 'Marcar linhas',
            'como' => 'A caixa do cabeçalho marca e desmarca todas as linhas. As operações em lote agem só sobre o que estiver marcado, e o envio sem nada marcado é recusado com aviso.',
        ],
        [
            'nome' => 'Anular e desfazer os selecionados',
            'como' => 'Marque as linhas, escreva um motivo e use "Anular": ele vale para todos os créditos selecionados, e cada pessoa recebe o aviso no sino. "Desfazer anulação" devolve os pontos dos selecionados.',
            'observacao' => 'Desfazer alcança só o que uma pessoa anulou. O crédito anulado pelo sistema volta sozinho quando a presença que faltava for registrada outra vez, e por isso não tem botão.',
        ],
        [
            'nome' => 'Exportar',
            'icone' => 'baixar',
            'como' => 'Baixa a lista filtrada em planilha de texto separada por ponto e vírgula, com bônus, nome, correio eletrônico, pontos, exigência atingida, horário, situação e motivo.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin'],
];
