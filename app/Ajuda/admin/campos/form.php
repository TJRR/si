<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Campo: novo/editar',
    'resumo' => 'Configuração de um campo de formulário dinâmico.',
    'operacoes' => [
        [
            'nome' => 'Rótulo / Tipo / Obrigatório',
            'como' => 'Tipo inclui texto, texto longo, número, CPF, e-mail, telefone, hiperlink do YouTube, hiperlink externo, seleção de tema/desafio, envio de PDF ou grupo repetível de participantes; os campos extras da tela mudam conforme o tipo escolhido.',
        ],
        [
            'nome' => '"Solução proposta"',
            'como' => 'Caixa de seleção que marca este campo como o texto principal da solução da equipe.',
            'observacao' => 'No máximo 1 campo marcado assim por trilha: é esse campo que alimenta o marcador usado nos Modelos de Documento.',
        ],
        [
            'nome' => 'Mín./Máx. participantes',
            'como' => 'Só aparece quando o tipo é "grupo de participantes": define o intervalo permitido de integrantes.',
        ],
    ],
    'conceitos' => [],
];
