<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Dúvida — detalhe/atendimento',
    'resumo' => 'Thread completa de uma dúvida, com histórico de escalonamento e anexos da pergunta/respostas. Quem pode agir: Administrador com escopo sobre o concurso da dúvida, ou o responsável atual designado a ela.',
    'operacoes' => [
        [
            'nome' => 'Responder',
            'icone' => 'responder',
            'como' => 'Textarea + anexo opcional. Notifica a equipe que registrou a dúvida.',
            'observacao' => 'Uma dúvida "Respondida" trava — não é mais possível responder nem escalar a partir daqui (só o participante pode reabri-la).',
        ],
        [
            'nome' => 'Escalar',
            'icone' => 'escalar',
            'como' => 'Escolha outro atendente para assumir a responsabilidade.',
        ],
        [
            'nome' => 'Histórico de escalonamento',
            'icone' => 'historico',
            'como' => 'Mostra quem escalou para quem, e quando.',
        ],
        [
            'nome' => 'Transformar em pergunta frequente',
            'icone' => 'publicar',
            'como' => 'Aproveita a dúvida e a resposta como pergunta/resposta genérica no banco de FAQ. Só aparece quando já existe resposta registrada, e só para Administrador e Suporte com perfil global.',
            'observacao' => 'Abre um formulário para edição obrigatória — nunca copia com um clique. A dúvida original não muda de status nem de conteúdo, e a equipe autora não é notificada: o item publicado é reescrito e genérico.',
        ],
        [
            'nome' => 'Selo "Já virou FAQ"',
            'como' => 'Aparece no cabeçalho quando a dúvida já gerou pergunta(s) frequente(s), com link para o item. Serve para não promover a mesma dúvida duas vezes sem perceber.',
        ],
    ],
    'conceitos' => ['atendimento_fila_escalonamento'],
];
