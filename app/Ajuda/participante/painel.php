<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Minha inscrição',
    'resumo' => 'Tela central do participante: equipe, trilha, tema/desafio escolhido, integrantes com status de homologação, e as etapas de submissão já liberadas para a equipe.',
    'operacoes' => [
        [
            'nome' => 'Mentoria / Oficinas / Dúvidas / Requerimentos',
            'como' => 'Botões condicionais — só aparecem quando aquele recurso está disponível para a sua trilha/etapa atual.',
        ],
        [
            'nome' => 'Editar equipe',
            'como' => 'Só disponível para o líder.',
        ],
        [
            'nome' => 'Editar integrante',
            'como' => 'Cada integrante só edita a si mesmo (inclusive o líder) — bloqueado no servidor com 403 se tentar editar outro.',
        ],
        [
            'nome' => 'Incluir e-mail / Promover / Excluir integrante',
            'como' => 'Só o líder, sempre com confirmação para ações que mudam a equipe.',
            'observacao' => 'Não é possível excluir o líder, nem reduzir a equipe abaixo de 2 integrantes.',
        ],
        [
            'nome' => 'Preencher / Ver notas e feedback',
            'como' => 'Por etapa — "Preencher" leva ao formulário de submissão; "Ver notas e feedback" só aparece depois que o resultado da etapa é publicado.',
            'observacao' => 'Uma etapa só aparece se a equipe estiver homologada. Cada etapa pode estar bloqueada por um motivo próprio — fora do prazo, ou equipe não classificada na etapa anterior.',
        ],
    ],
    'conceitos' => ['cadastro_pendente_aprovacao'],
];
