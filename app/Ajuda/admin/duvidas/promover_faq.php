<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Transformar dúvida em pergunta frequente',
    'resumo' => 'Aproveita uma dúvida já respondida como pergunta/resposta genérica no banco de FAQ. A dúvida original e a resposta enviada à equipe não mudam em nada: o que se cria aqui é um item novo e independente, sem vínculo visível com a equipe que perguntou. Disponível para Administrador e Suporte com perfil global.',
    'operacoes' => [
        [
            'nome' => 'Edição obrigatória',
            'como' => 'O formulário abre pré-preenchido, mas nunca grava direto. Reescreva a pergunta e a resposta em termos genéricos antes de salvar.',
            'observacao' => 'O texto veio de um participante e pode conter nome da equipe, nome do projeto, dado pessoal e detalhe de submissão sob sigilo, e o destino dele é uma página pública. Este é o único momento de retirar isso.',
        ],
        [
            'nome' => 'Pergunta',
            'como' => 'Máximo de 255 caracteres. O texto de uma dúvida costuma ser bem mais longo; quando é, o preenchimento vem cortado e a tela avisa.',
        ],
        [
            'nome' => 'Resposta',
            'como' => 'Vem preenchida com a resposta mais recente da dúvida. Se a dúvida foi reaberta e tem mais de uma resposta, as anteriores aparecem no fim da página em somente-leitura, para copiar trecho.',
        ],
        [
            'nome' => 'Destino',
            'como' => 'Duas opções, e só duas: "Só no banco geral" guarda a pergunta sem publicar em lugar nenhum; "Banco geral e ativa na edição escolhida" também a liga àquela edição, e aí ela passa a aparecer na página inicial. Toda pergunta nasce obrigatoriamente no banco geral; ver o conceito abaixo.',
            'observacao' => 'A edição sugerida é a da própria dúvida.',
        ],
        [
            'nome' => 'Anexos',
            'como' => 'Não acompanham. Os anexos da dúvida e das respostas ficam em área privada; o item de FAQ nunca referencia arquivo.',
        ],
        [
            'nome' => 'Promover duas vezes',
            'como' => 'Se a dúvida já tiver gerado pergunta, a tela avisa e lista o que já existe, mas não impede: uma dúvida longa pode render mais de uma pergunta.',
        ],
    ],
    'conceitos' => ['banco_global_vs_edicao'],
];
