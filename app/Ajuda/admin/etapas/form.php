<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Etapa — dados gerais',
    'resumo' => 'Cada Etapa é uma fase do processo dentro de uma Trilha (ex.: "Cadastro de Equipe", "1ª Fase", "Final"), com seu próprio período, regra de passagem para a próxima etapa e configuração de avaliação.',
    'operacoes' => [
        [
            'nome' => 'Regra de transição para a próxima etapa',
            'como' => 'Escolha Nenhuma (etapa final ou sem corte), Número fixo de equipes classificadas, Percentual classificado ou Nota de corte, e preencha o valor correspondente no campo logo abaixo.',
        ],
        [
            'nome' => 'Prazo final para submissão',
            'como' => 'Data-limite para a equipe enviar o formulário desta etapa.',
            'observacao' => 'Não pode ser anterior à Data de início nem posterior à Data de fim — o servidor recusa salvar se isso acontecer. Se o mecanismo de avaliação for "Por Avaliadores" e esse prazo ficar em branco, a lista de Etapas mostra um alerta visual.',
        ],
        [
            'nome' => 'Formulário dinâmico vinculado',
            'como' => 'Escolha qual formulário (já criado em Formulários) a equipe preenche nesta etapa.',
        ],
        [
            'nome' => 'Mecanismo de avaliação',
            'como' => 'Nenhuma (etapa sem avaliação), Pelo Administrador (ex.: homologação de cadastro) ou Por Avaliadores. Escolher "Por Avaliadores" revela o bloco "Configuração de avaliação desta etapa" logo abaixo.',
        ],
        [
            'nome' => 'Divulgação pública do resultado',
            'como' => 'Define o que qualquer visitante sem login vê em "Ver resultado" depois de publicado: nada, só a lista de classificados, o ranking completo, ou ranking + material enviado pela equipe. Vale para qualquer mecanismo de avaliação, não só "Por Avaliadores".',
        ],
        [
            'nome' => 'Designação de avaliadores',
            'como' => 'Só aparece com mecanismo "Por Avaliadores". Admin atribui manualmente, Todo avaliador vê tudo (aberto), Distribuição automática balanceada, ou Sorteio garantindo 1 avaliador de cada categoria.',
            'observacao' => 'O sorteio por categoria depende de categorias cadastradas e de vagas configuradas na tela "Vagas por categoria" da etapa.',
        ],
        [
            'nome' => 'Sigilo da avaliação',
            'como' => 'Aberta (o avaliador vê a equipe) ou Cega (não vê).',
        ],
        [
            'nome' => 'Avanço para a próxima etapa',
            'como' => 'Manual (Admin confirma o corte antes de liberar) ou Automático (assim que as notas de todas as submissões estiverem completas, o resultado é publicado sozinho, disparado a partir do próprio lançamento da última nota — não existe um cron rodando isso em segundo plano).',
        ],
        [
            'nome' => 'Salvar',
            'como' => 'Grava a etapa. Só disponível para Administrador — Suporte vê a mesma tela com todos os campos desabilitados.',
        ],
    ],
    'conceitos' => ['sigilo_anonimato', 'permissao_suporte_admin'],
];
