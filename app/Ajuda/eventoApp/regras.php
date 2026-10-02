<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Regras do jogo',
    'resumo' => 'Quanto vale cada forma de pontuar neste evento. Os valores desta tela são os que o sistema de fato credita, lidos do cadastro da organização na hora em que você abre a tela.',
    'operacoes' => [
        [
            'nome' => 'Presença em atividades',
            'como' => 'Pontos por tipo de atividade, e o extra para quem confirma presença com antecedência. Atividades com valor próprio aparecem uma a uma.',
        ],
        [
            'nome' => 'Credenciamento, competições e bônus',
            'como' => 'O credenciamento no local e a participação em competições são lidos em "Ler código". Os bônus mostram o que cada um pede e quanto vale; cada bônus vale uma vez.',
        ],
        [
            'nome' => 'Conexões, estandes e divulgação',
            'como' => 'Quanto vale cada conexão, cada visita a estande e cada comprovação de divulgação, com os limites de cada rede.',
        ],
        [
            'nome' => 'Classificação',
            'como' => 'Como a classificação é calculada, o que você vê dela e os critérios usados em caso de empate, na ordem em que valem.',
        ],
    ],
    'conceitos' => [],
];
