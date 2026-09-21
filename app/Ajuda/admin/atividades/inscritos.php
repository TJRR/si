<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Atividade: Inscritos',
    'resumo' => 'Quem se inscreveu nesta atividade, separado em Confirmadas e Lista de espera. O resumo de ocupação no topo mostra quantas vagas já estão ocupadas antes de qualquer decisão.',
    'operacoes' => [
        [
            'nome' => 'Confirmar (lista de espera)',
            'como' => 'Move a inscrição de "Lista de espera" para "Confirmadas" e avisa o participante por e-mail e pelo sino de notificações.',
            'observacao' => 'Não reconfere automaticamente o limite de vagas: a decisão de confirmar além da capacidade, se for o caso, é do Administrador, com a ocupação já visível no resumo do topo.',
        ],
        [
            'nome' => 'Exportar (.csv)',
            'como' => 'Gera a lista de inscritos confirmados nesta atividade, com as colunas exigidas pela EJURR (Educa Enfam), incluindo os facilitadores vinculados. Quem está na lista de espera não entra na exportação.',
        ],
    ],
    'conceitos' => [],
];
