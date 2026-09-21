<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Trabalhos: Configurações',
    'resumo' => 'Regras do processo de submissão e avaliação de artigos e resumos expandidos deste evento. Cada valor aqui vale só para esta edição, nunca fica fixo no sistema.',
    'operacoes' => [
        [
            'nome' => 'Prazos',
            'como' => 'Abertura/fim da submissão e início/fim da avaliação. Fora da janela de submissão, o formulário público mostra um aviso em vez do formulário.',
        ],
        [
            'nome' => 'Autoria e duplicidade',
            'como' => 'Quantidade máxima de autores (incluindo o principal) e se o mesmo CPF pode constar em mais de um trabalho deste evento.',
        ],
        [
            'nome' => 'Avaliação',
            'como' => 'Quantidade de avaliadores por trabalho, se a avaliação é às cegas, como combinar as notas de mais de um avaliador (média ou mediana), nota de corte e regra de seleção entre os aprovados.',
        ],
        [
            'nome' => 'Formulário de submissão',
            'como' => 'Quais dos métodos de envio ficam disponíveis (texto direto, endereço eletrônico, documento editável, documento em PDF), extensões aceitas e tamanho máximo do arquivo.',
        ],
        [
            'nome' => 'Situação',
            'como' => 'Rascunho (formulário indisponível), Publicado (submissão aberta a quem estiver no prazo) ou Encerrado.',
        ],
    ],
    'conceitos' => ['sigilo_anonimato'],
];
