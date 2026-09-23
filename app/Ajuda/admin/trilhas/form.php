<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Trilha (novo/editar)',
    'resumo' => 'Dados gerais de uma trilha (categoria) dentro de um concurso.',
    'operacoes' => [
        [
            'nome' => 'Resultado final na página pública',
            'como' => 'Oculto (padrão), só as colocações com destaque cadastrado, ou classificação completa com Nota Final. Só vale depois que o resultado final estiver publicado em Apuração.',
        ],
        [
            'nome' => 'Nome / Descrição / Ordem',
            'como' => 'Ordem define a posição de exibição entre as trilhas.',
        ],
        [
            'nome' => 'Mínimo de integrantes homologados',
            'como' => 'Usado na página pública de equipes homologadas dessa trilha; não bloqueia nada por si só na homologação.',
        ],
        [
            'nome' => 'Ativa',
            'como' => 'Controla se a trilha aparece nas telas públicas.',
        ],
    ],
    'conceitos' => [],
];
