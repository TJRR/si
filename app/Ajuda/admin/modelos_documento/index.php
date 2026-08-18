<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Modelos de Documento',
    'resumo' => 'Modelos de requerimento vinculados a uma Etapa — cada modelo vira um botão "Iniciar" para a equipe no painel do participante. Só Administrador tem acesso.',
    'operacoes' => [
        [
            'nome' => '+ Novo modelo',
            'como' => 'Abre o formulário de um novo modelo.',
        ],
        [
            'nome' => 'Editar / Remover',
            'icone' => 'editar',
            'como' => 'Altera ou apaga um modelo.',
            'observacao' => 'Remover fica bloqueado se já existir algum requerimento gerado a partir do modelo.',
        ],
        [
            'nome' => 'Expurgar documentos desta etapa',
            'como' => 'Apaga os PDFs e anexos dos requerimentos já decididos (aprovados/recusados) desta etapa, mantendo o registro sem os arquivos.',
            'observacao' => 'Exige redigitar o nome da etapa para confirmar, só funciona depois da data final da etapa, e é irreversível — trata-se de retenção de dados, não de limpeza rotineira.',
        ],
        [
            'nome' => 'Reordenar',
            'como' => 'Ver conceito "Reordenar por arraste" abaixo.',
        ],
    ],
    'conceitos' => ['reordenar_arraste'],
];
