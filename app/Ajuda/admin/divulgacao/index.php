<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Divulgação',
    'resumo' => 'Acompanhamento do módulo: se está ativado, o período que vale, os números do evento e os totais por rede, por ação e por dia. A lista do que cada pessoa enviou fica na aba "Comprovações".',
    'operacoes' => [
        [
            'nome' => 'Situação e período',
            'como' => 'Mostra se a divulgação está ativada e qual período o Administrador definiu em Configurações. Sem datas preenchidas, a tela diz que não há limite de data: vale enquanto estiver ativada. Com a divulgação desativada, nenhum período é mostrado, porque não há período nenhum valendo.',
        ],
        [
            'nome' => 'Números do evento',
            'como' => 'Comprovações válidas, pontos creditados no total, participantes que enviaram e quantas foram anuladas. Comprovação anulada ou excluída sai de todas as somas.',
        ],
        [
            'nome' => 'Totais por rede e por ação',
            'como' => 'Quantas comprovações e quantos pontos válidos cada rede gerou, separados por publicação e por passar a seguir. Só aparecem as redes que de fato receberam envio.',
        ],
        [
            'nome' => 'Totais por dia',
            'como' => 'Quantas comprovações chegaram em cada dia, para acompanhar o movimento durante o evento.',
        ],
        [
            'nome' => 'Onde conferir e corrigir',
            'como' => 'A aba "Comprovações" tem a lista nominal com filtros, seleção e as operações de anular, desfazer anulação, apagar imagem e excluir.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin'],
];
