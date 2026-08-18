<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Designação de Avaliadores',
    'resumo' => 'Quem avalia o quê, nesta etapa — o comportamento da tela muda conforme o modo de designação escolhido em Etapas — dados gerais. Em modo "aberto" (qualquer avaliador vê tudo), não há designação individual — a tela mostra só um aviso informativo, sem tabela de atribuição.',
    'operacoes' => [
        [
            'nome' => 'Filtrar por avaliador/status',
            'como' => 'Reduz a lista para conferir uma pessoa ou situação específica.',
        ],
        [
            'nome' => 'Atribuir aos selecionados',
            'como' => 'Marque submissões via checkbox, escolha o avaliador no seletor, e clique no botão para atribuir em massa (modo manual).',
        ],
        [
            'nome' => 'Remover designação',
            'icone' => 'remover',
            'como' => 'Tira um avaliador de uma submissão específica.',
            'observacao' => 'Fica ausente ou travada (🔒) se a designação veio de sorteio já aceito, ou se o avaliador já lançou nota — nesses casos não é mais possível remover por aqui.',
        ],
        [
            'nome' => 'Ver progresso por avaliador',
            'como' => 'Leva à tela de Progresso da avaliação desta etapa.',
        ],
        [
            'nome' => 'Distribuir automaticamente',
            'como' => 'Só aparece em modo automático/sorteio — abre uma prévia da distribuição antes de confirmar.',
        ],
        [
            'nome' => 'Vagas por categoria',
            'como' => 'Só aparece em modo sorteio por categoria — link para configurar quantas vagas cada categoria tem nesta etapa.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin'],
];
