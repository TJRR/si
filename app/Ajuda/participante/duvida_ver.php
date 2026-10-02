<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Ver dúvida',
    'resumo' => 'Esta é a conversa completa de uma dúvida da sua equipe: a pergunta, cada resposta da organização e os anexos, com a foto de quem escreveu cada mensagem.',
    'operacoes' => [
        [
            'nome' => 'Reabrir dúvida',
            'como' => 'Se a resposta não resolveu, escreva a continuação da conversa no campo de texto, anexe um arquivo se precisar e envie. A dúvida volta para a fila da organização, e você é avisado quando houver resposta nova.',
            'observacao' => 'O botão só aparece depois que a dúvida foi respondida. Enquanto ela estiver em análise, aguarde a resposta.',
        ],
    ],
    'conceitos' => [],
];
