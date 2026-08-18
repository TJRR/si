<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Documentos',
    'resumo' => 'Editais, anexos, retificações, resultados e atas publicados na home de um concurso, com versionamento automático.',
    'operacoes' => [
        [
            'nome' => '+ Novo',
            'como' => 'Tipo, trilha (opcional), título e o arquivo PDF.',
            'observacao' => 'Um novo upload com o mesmo tipo + título vira automaticamente uma nova versão do mesmo documento — nunca sobrescreve o arquivo anterior.',
        ],
        [
            'nome' => 'Editar',
            'icone' => 'editar',
            'como' => 'Altera só os metadados (tipo, trilha, título) — não o arquivo.',
        ],
        [
            'nome' => 'Baixar',
            'icone' => 'baixar',
            'como' => 'Baixa a versão atual do arquivo.',
        ],
        [
            'nome' => 'Ver histórico de versões',
            'icone' => 'historico',
            'como' => 'Mostra as versões anteriores do mesmo documento.',
        ],
        [
            'nome' => 'Despublicar / Republicar',
            'icone' => 'despublicar',
            'como' => 'Despublicar tira o documento da home sem apagar nada; Republicar coloca de volta.',
        ],
        [
            'nome' => 'Remover todas as versões',
            'icone' => 'remover',
            'como' => 'Apaga o documento e todo o seu histórico.',
            'observacao' => 'Irreversível, com confirmação reforçada — diferente de Despublicar, aqui não sobra nada pra recuperar.',
        ],
        [
            'nome' => 'Reordenar',
            'como' => 'Ver conceito "Reordenar por arraste" abaixo.',
        ],
    ],
    'conceitos' => ['nunca_apaga_so_versiona', 'reordenar_arraste'],
];
