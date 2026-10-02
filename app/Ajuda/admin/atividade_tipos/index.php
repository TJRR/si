<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Tipos de atividade',
    'resumo' => 'Catálogo do evento com os tipos que viram etiqueta colorida nas seções Destaques e Programação da página pública, e que dão os pontos de presença das atividades.',
    'operacoes' => [
        ['nome' => 'Cadastrar tipo', 'como' => 'Nome, cor e pontos. Cada evento tem os seus tipos; nenhuma atividade é obrigada a ter tipo.'],
        [
            'nome' => 'Pontos de presença',
            'como' => 'Quanto vale confirmar presença numa atividade deste tipo, pela sala ou pela internet. Zero: o tipo não pontua. Cada atividade pode ter valor próprio no cadastro dela.',
        ],
        [
            'nome' => 'Extra de pontualidade',
            'como' => 'Pontos a mais para quem confirma presença até o número de minutos antes do início definido em Gamificação, Configurações. Zero: o tipo não tem extra.',
            'observacao' => 'Mudar os valores vale só para as próximas presenças. A presença já pontuada guarda os pontos do momento da leitura.',
        ],
        ['nome' => 'Remover', 'icone' => 'remover', 'como' => 'Bloqueado enquanto alguma atividade estiver usando o tipo.'],
        ['nome' => 'Arrastar', 'como' => 'Define a ordem da lista na hora de escolher o tipo da atividade.'],
    ],
    'conceitos' => ['reordenar_arraste'],
];
