<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Homologação (Inscritos)',
    'resumo' => 'Revisão individual de cada integrante inscrito numa trilha — é o "mecanismo Pelo Administrador" da etapa de Cadastro de Equipe.',
    'operacoes' => [
        [
            'nome' => 'Publicar / Despublicar página pública',
            'icone' => 'publicar',
            'como' => 'Coloca (ou tira) do ar a página pública de equipes homologadas desta trilha. Só Administrador.',
        ],
        [
            'nome' => 'Filtrar por status',
            'como' => 'Reduz a lista de inscritos por situação de homologação.',
        ],
        [
            'nome' => 'Homologar / Rejeitar',
            'icone' => 'publicar',
            'como' => 'Individual (por linha) ou em massa (marque as checkboxes e use "Homologar selecionados"/"Rejeitar selecionados"). Disponível para Administrador e Suporte.',
            'observacao' => 'Homologar libera acesso automaticamente — cria a conta do integrante — mas só roda essa criação uma vez. Se o e-mail foi incluído depois da homologação, é preciso usar "Convidar acesso" manualmente. Rejeitar limpa notificações anteriores e avisa o(s) usuário(s) envolvido(s).',
        ],
        [
            'nome' => 'Convidar acesso',
            'icone' => 'convidar',
            'como' => 'Só aparece quando o integrante já está homologado, tem e-mail cadastrado, e ainda não tem conta.',
        ],
    ],
    'conceitos' => ['publicar_trava', 'cadastro_pendente_aprovacao'],
];
