<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Editar equipe',
    'resumo' => 'Nesta tela o líder atualiza os dados gerais da equipe. Os demais integrantes não têm acesso a ela.',
    'operacoes' => [
        [
            'nome' => 'Passo a passo',
            'como' => '1. Corrija o nome da equipe, o vínculo institucional ou as observações. 2. Clique em "Salvar". Os dados novos passam a valer na hora, inclusive nas telas da organização.',
        ],
    ],
    'conceitos' => [],
];
