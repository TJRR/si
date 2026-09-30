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
            'como' => 'A lista pode ser reduzida a um bônus específico e à situação (válidos ou anulados). A seleção é aplicada na hora, sem botão.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin'],
];
