<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Formulário de Inscrição (nova equipe)',
    'resumo' => 'Ponto de entrada público, sem login — é o único jeito de cadastrar uma equipe nova no sistema. Todos os integrantes são enviados de uma vez, via um campo de grupo de participantes — não existe um fluxo de "adicionar integrante depois" nesta tela.',
    'operacoes' => [
        [
            'nome' => 'Enviar inscrição',
            'como' => 'Envia o cadastro da equipe.',
        ],
    ],
    'conceitos' => ['cadastro_pendente_aprovacao'],
];
