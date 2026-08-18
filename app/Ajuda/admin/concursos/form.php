<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Concurso — novo/editar',
    'resumo' => 'Dados gerais de uma edição do Prêmio de Inovação.',
    'operacoes' => [
        [
            'nome' => 'Nome',
            'como' => 'Obrigatório — ex.: "5º Prêmio de Inovação".',
        ],
        [
            'nome' => 'Data de início / fim',
            'como' => 'Período de referência da edição — não trava por si só nenhuma outra tela (os prazos que realmente bloqueiam ficam nas Etapas).',
        ],
        [
            'nome' => 'Status',
            'como' => 'Rascunho, Ativo ou Encerrado.',
        ],
        [
            'nome' => 'Salvar',
            'como' => 'Ao criar um concurso novo, você é levado direto para Trilhas em seguida. Criação de concurso novo é restrita a Administrador global; edição é aberta a Administrador (grava) e Suporte (só vê).',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin'],
];
