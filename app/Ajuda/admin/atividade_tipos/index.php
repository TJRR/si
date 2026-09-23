<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Tipos de atividade',
    'resumo' => 'Catálogo do evento com os tipos que viram etiqueta colorida nas seções Destaques e Programação da página pública.',
    'operacoes' => [
        ['nome' => 'Cadastrar tipo', 'como' => 'Nome e cor. Cada evento tem os seus tipos; nenhuma atividade é obrigada a ter tipo.'],
        ['nome' => 'Remover', 'icone' => 'remover', 'como' => 'Bloqueado enquanto alguma atividade estiver usando o tipo.'],
        ['nome' => 'Arrastar', 'como' => 'Define a ordem da lista na hora de escolher o tipo da atividade.'],
    ],
    'conceitos' => ['reordenar_arraste'],
];
