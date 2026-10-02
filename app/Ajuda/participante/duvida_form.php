<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Registrar dúvida',
    'resumo' => 'Use esta tela para perguntar algo à organização do concurso sobre a sua participação: regras, prazos, etapas ou qualquer ponto que não ficou claro. A pergunta fica registrada e a resposta chega por aqui mesmo.',
    'operacoes' => [
        [
            'nome' => 'Passo a passo',
            'como' => '1. Escreva a sua pergunta no campo de texto, com o máximo de detalhes que ajudem a entender o caso. 2. Se quiser, anexe um arquivo em PDF ou uma imagem, por exemplo a captura de uma tela com erro. 3. Clique em "Registrar".',
        ],
        [
            'nome' => 'O que acontece depois',
            'como' => 'A dúvida aparece na lista "Minhas dúvidas" com a situação "Recebida", e todos os administradores do concurso da sua trilha recebem um aviso. Quando alguém responder, a situação muda para "Respondida" e você é avisado.',
            'observacao' => 'A dúvida é da equipe: qualquer integrante consegue ver a pergunta e a resposta.',
        ],
    ],
    'conceitos' => [],
];
