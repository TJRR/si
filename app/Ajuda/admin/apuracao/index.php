<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Apuração da trilha',
    'resumo' => 'Tela que reúne, num só lugar, a Fórmula da Nota Final (NF), o gerenciamento de Desempate e o resultado/ranking final da trilha.',
    'operacoes' => [
        [
            'nome' => 'Fórmula NF',
            'como' => 'Mesmo campo da tela "Fórmula da Trilha" — editar aqui ou lá grava o mesmo dado.',
        ],
        [
            'nome' => 'Gerenciar desempate',
            'como' => 'Link para a tela de Desempate.',
        ],
        [
            'nome' => 'Confirmar e publicar',
            'icone' => 'publicar',
            'como' => 'Publica o ranking final da trilha numa página pública.',
            'observacao' => 'A prévia mostrada nesta tela é recalculada a cada acesso, até você publicar — depois de publicado, o que ficou registrado não muda sozinho mesmo que dados mudem depois. O ranking só fica disponível para publicar quando todas as etapas da trilha já tiverem seu próprio resultado publicado individualmente.',
        ],
        [
            'nome' => 'Reabrir',
            'icone' => 'despublicar',
            'como' => 'Apaga o resultado publicado e volta a mostrar a prévia recalculada.',
        ],
    ],
    'conceitos' => ['publicar_trava', 'permissao_suporte_admin'],
];
