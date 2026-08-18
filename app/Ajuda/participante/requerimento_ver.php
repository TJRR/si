<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Ver requerimento',
    'resumo' => 'Detalhe de um requerimento e, enquanto ele estiver "Aguardando documento assinado", o fluxo de gerar/assinar/enviar o PDF.',
    'operacoes' => [
        [
            'nome' => 'Gerar PDF',
            'como' => 'Necessidade é editável até o documento ser assinado — gerar de novo substitui a versão anterior (não assinada ainda).',
        ],
        [
            'nome' => 'Enviar documento assinado',
            'como' => 'Upload do PDF já assinado no gov.br (limite de tamanho mostrado na própria tela).',
            'observacao' => 'O servidor recusa o envio se o PDF não tiver o marcador de assinatura digital embutida (é uma checagem estrutural, não uma verificação criptográfica completa — a validação de fato acontece na tela de atendimento, via ITI).',
        ],
        [
            'nome' => 'Descartar rascunho',
            'como' => 'Apaga o requerimento, com confirmação.',
            'observacao' => 'Só disponível se nada foi enviado ainda (nenhum documento assinado).',
        ],
        [
            'nome' => 'Baixar documento/anexos de resposta',
            'como' => 'Disponível quando o atendente já respondeu ou quando o documento não foi expurgado.',
        ],
    ],
    'conceitos' => [],
];
