<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Painel',
    'resumo' => 'Primeira tela ao entrar na área administrativa — visão geral do sistema (contadores) e três seções recolhíveis: Dúvidas, Requerimentos e Progresso da avaliação. Cada seção começa fechada, e reabre sozinha se o filtro dela acabou de ser usado.',
    'operacoes' => [
        [
            'nome' => 'Cartões numéricos',
            'como' => 'Participantes, Equipes, Avaliadores, Concursos ativos, Concursos realizados e Cadastros pendentes — visão geral, sem filtro, sem clique.',
        ],
        [
            'nome' => 'Seção Dúvidas',
            'como' => 'Administrador vê contadores (Recebidas/Escaladas/Respondidas) e a tabela completa, com filtro de status. Qualquer perfil (Administrador, Suporte, Colaborador) vê "Escaladas para mim" — a fila pessoal.',
            'pills' => [
                ['cor' => 'azul', 'rotulo' => 'Recebida'],
                ['cor' => 'laranja', 'rotulo' => 'Em análise'],
                ['cor' => 'verde', 'rotulo' => 'Respondida'],
            ],
        ],
        [
            'nome' => 'Seção Requerimentos',
            'como' => 'Mesmo padrão de Dúvidas, com status extras de assinatura/decisão.',
            'pills' => [
                ['cor' => 'cinza', 'rotulo' => 'Aguardando assinatura'],
                ['cor' => 'roxo', 'rotulo' => 'Esclarecimento solicitado'],
                ['cor' => 'verde', 'rotulo' => 'Aprovado'],
                ['cor' => 'vermelho', 'rotulo' => 'Recusado/Revogado'],
            ],
        ],
        [
            'nome' => 'SLA (coluna nas duas tabelas)',
            'como' => 'Calculado desde a última movimentação — sem clique, é só leitura.',
            'pills' => [
                ['cor' => 'verde', 'rotulo' => 'Em dia'],
                ['cor' => 'vermelho', 'rotulo' => 'Atrasada'],
            ],
        ],
        [
            'nome' => 'Ver / Responder / Escalar',
            'icone' => 'ver',
            'como' => 'Ícones por linha, levam direto ao detalhe (Ver) ou já abrem o formulário certo dentro dele (Responder/Escalar). Administrador também tem "Retomar pra fila geral" na tabela completa.',
        ],
        [
            'nome' => 'Seção Progresso da avaliação',
            'como' => 'Lista as etapas com avaliação em andamento, com barra de percentual. Clique numa etapa para abrir o progresso por avaliador.',
        ],
    ],
    'conceitos' => ['atendimento_fila_escalonamento'],
];
