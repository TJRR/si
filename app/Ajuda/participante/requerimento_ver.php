<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Ver requerimento',
    'resumo' => 'Aqui você acompanha um requerimento da equipe. Enquanto ele estiver "Aguardando documento assinado", é também aqui que você gera o PDF, assina e envia o arquivo assinado.',
    'operacoes' => [
        [
            'nome' => 'Gerar PDF',
            'como' => 'Enquanto o documento não for enviado assinado, você pode corrigir a necessidade e gerar o PDF de novo. A versão nova substitui a anterior.',
        ],
        [
            'nome' => 'Enviar documento assinado',
            'como' => '1. Assine o PDF no gov.br. 2. Escolha o arquivo assinado e envie. O limite de tamanho aparece na própria tela. Depois do envio, o pedido entra para análise da organização.',
            'observacao' => 'Se o arquivo não trouxer assinatura digital, o envio é recusado e a tela avisa. Assine pelo gov.br e envie de novo.',
        ],
        [
            'nome' => 'Descartar rascunho',
            'como' => 'Apaga o requerimento, depois de uma confirmação. Só aparece enquanto nenhum documento assinado foi enviado.',
        ],
        [
            'nome' => 'Baixar a resposta',
            'como' => 'Quando a organização responder, o documento e os anexos da resposta ficam disponíveis para baixar aqui.',
        ],
    ],
    'conceitos' => [],
];
