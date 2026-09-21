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
            'como' => 'Mesmo campo da tela "Fórmula da Trilha": editar aqui ou lá grava o mesmo dado. Inclui também as casas decimais de exibição da NF (padrão 2).',
        ],
        [
            'nome' => 'Gerenciar desempate',
            'como' => 'Hiperlink para a tela de Desempate.',
        ],
        [
            'nome' => 'Confirmar e publicar',
            'icone' => 'publicar',
            'como' => 'Publica o ranking final da trilha numa página pública.',
            'observacao' => 'A prévia mostrada nesta tela é recalculada a cada acesso, até você publicar. Depois de publicado, o que ficou registrado não muda sozinho mesmo que dados mudem depois. O ranking só fica disponível para publicar quando as etapas que a fórmula da NF realmente usa (as variáveis de Nota da Etapa (NE) que aparecem na expressão) já tiverem resultado publicado; uma etapa fora da fórmula, como "Cadastro das Equipes", nunca é exigida.',
        ],
        [
            'nome' => 'Reabrir',
            'icone' => 'despublicar',
            'como' => 'Apaga o resultado publicado e volta a mostrar a prévia recalculada.',
        ],
        [
            'nome' => 'Destaque público',
            'icone' => 'ver',
            'como' => 'Coluna que aparece só depois de publicado: o lápis abre a tela de resumo/imagem de destaque de cada colocação (mesma tela usada em Edições Anteriores).',
        ],
    ],
    'conceitos' => ['publicar_trava', 'permissao_suporte_admin'],
];
