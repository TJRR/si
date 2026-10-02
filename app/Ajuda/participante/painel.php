<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Minha inscrição',
    'resumo' => 'Esta é a tela principal da sua participação no concurso. Ela mostra a equipe, a trilha, o tema e o desafio escolhidos, os integrantes com a situação de cada um e as etapas de envio já abertas para a equipe. É daqui que você chega a todas as outras telas.',
    'operacoes' => [
        [
            'nome' => 'Mentoria, Oficinas, Dúvidas e Requerimentos',
            'como' => 'Os botões aparecem só quando o recurso está disponível para a sua trilha e etapa. Se todos os horários de mentoria ou de oficina forem de uma etapa em que a sua equipe não está, o botão não aparece, em vez de abrir uma tela vazia.',
        ],
        [
            'nome' => 'Editar equipe',
            'como' => 'Só o líder vê este botão. Ele leva aos dados gerais da equipe.',
        ],
        [
            'nome' => 'Editar integrante',
            'como' => 'Cada pessoa edita só os próprios dados, inclusive o líder.',
        ],
        [
            'nome' => 'Incluir e-mail, promover a líder e excluir integrante',
            'como' => 'Só o líder faz essas mudanças na equipe, e cada uma pede confirmação antes de valer.',
            'observacao' => 'O líder não pode ser excluído, e a equipe não pode ficar com menos de 2 integrantes.',
        ],
        [
            'nome' => 'Preencher e ver notas',
            'como' => 'Para cada etapa aberta: "Preencher" leva ao formulário de envio da etapa; "Ver notas e comentário" aparece depois que a organização publica o resultado daquela etapa.',
            'observacao' => 'As etapas só aparecem para equipe homologada. Uma etapa pode estar fechada por estar fora do prazo ou porque a equipe não foi classificada na etapa anterior; o motivo aparece ao lado dela.',
        ],
    ],
    'conceitos' => ['cadastro_pendente_aprovacao'],
];
