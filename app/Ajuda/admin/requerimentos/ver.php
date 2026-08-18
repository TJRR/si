<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Requerimento — detalhe/atendimento',
    'resumo' => 'Pedido formal com PDF assinado digitalmente, protocolado por uma equipe a partir de um Modelo de Documento.',
    'operacoes' => [
        [
            'nome' => 'Baixar PDF assinado',
            'como' => 'Baixa o documento tal como enviado pela equipe.',
            'observacao' => 'Se o documento já foi "expurgado" (retenção de dados vencida), o download fica bloqueado.',
        ],
        [
            'nome' => 'Validar automaticamente no ITI',
            'como' => 'Consulta validar.iti.gov.br para conferir a assinatura, via uma janela pública temporária de uso único (3 minutos).',
            'observacao' => 'Alerta se nome/CPF da assinatura não baterem com o cadastro — mas nunca dispensa a conferência manual: "Confirmo que validei a assinatura" é obrigatório marcar antes de aprovar, mesmo com a validação automática ok.',
        ],
        [
            'nome' => 'Aprovado / Recusado',
            'icone' => 'publicar',
            'como' => 'Desfecho final do requerimento — decide o pedido.',
            'observacao' => 'Exclusivo de Administrador. Suporte e Colaborador não têm essa opção — só podem "Pedir esclarecimentos" ou escalar.',
        ],
        [
            'nome' => 'Pedir esclarecimentos',
            'como' => 'Qualquer atendente pode usar — pede à equipe mais informação antes de decidir.',
        ],
        [
            'nome' => 'Revogar acesso',
            'como' => 'Desfaz um requerimento já Aprovado.',
            'observacao' => 'Só disponível se o status for Aprovado, só para Administrador, com confirmação reforçada — é irreversível.',
        ],
        [
            'nome' => 'Escalar / Retomar pra fila geral',
            'icone' => 'escalar',
            'como' => 'Escalar passa a responsabilidade para outro atendente. "Retomar pra fila geral" só aparece para Administrador.',
        ],
    ],
    'conceitos' => ['atendimento_fila_escalonamento'],
];
