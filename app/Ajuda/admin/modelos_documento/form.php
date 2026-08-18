<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Modelo de Documento — novo/editar',
    'resumo' => 'Corpo de texto de um modelo de requerimento, com palavras-chave que são substituídas pelos dados reais da equipe ao gerar o PDF.',
    'operacoes' => [
        [
            'nome' => 'Corpo (editor rico)',
            'como' => 'Use as palavras-chave `[[placeholder]]` do painel/dicionário lateral para inserir dados que variam por equipe (ex.: nome da equipe, nome do líder).',
            'observacao' => 'O servidor rejeita salvar se o texto contiver um placeholder não reconhecido — a mensagem de erro lista qual.',
        ],
        [
            'nome' => 'Nome / Finalidade / Ativo',
            'como' => 'Nome e Finalidade aparecem para a equipe antes de iniciar o pedido; Ativo controla se o modelo fica disponível para uso.',
        ],
    ],
    'conceitos' => [],
];
