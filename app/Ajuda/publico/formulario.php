<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Formulário de submissão',
    'resumo' => 'Formulário dinâmico (campos configurados pelo Administrador) para enviar a submissão de uma etapa — exige estar logado como participante.',
    'operacoes' => [
        [
            'nome' => '+ Adicionar participante',
            'como' => 'Só aparece em campos do tipo "grupo de participantes" — adiciona uma linha repetível de dados.',
        ],
        [
            'nome' => 'Enviar',
            'como' => 'Envia a submissão.',
            'observacao' => 'Reenviar atualiza a submissão existente — não cria uma duplicata. Upload de PDF tem limite de 15MB. O acesso a esta tela segue as mesmas regras do painel do participante: equipe homologada, dentro do prazo, e classificada na etapa anterior (se houver corte).',
        ],
    ],
    'conceitos' => [],
];
