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
            'nome' => 'Recebimento do trabalho e inscrição dos autores',
            'como' => 'Com "Inscrever automaticamente os autores" ligada, o autor principal e cada coautor são inscritos no evento no ato da submissão e recebem um único e-mail, com o recebimento do trabalho e a inscrição. Coautor sem conta no sistema ganha uma, com endereço para definir a senha (vale por 7 dias). Com a opção desligada, só o autor principal recebe o e-mail de recebimento.',
            'observacao' => 'O texto do e-mail vem depois do protocolo e dos dados de acesso, que o sistema monta sozinho. Em branco, vale um texto padrão. O formulário de submissão avisa quando a inscrição automática está ligada.',
        ],
        [
            'nome' => 'Resultado para o autor',
            'como' => 'Define o que cada autor (principal e coautores) vê do próprio trabalho depois que o resultado é publicado na tela Resultado: a nota final, a posição na classificação e a média de cada critério. A situação final (aprovado ou reprovado) sempre aparece, e a nota de cada avaliador nunca aparece.',
            'observacao' => 'O texto do e-mail de aviso vem depois do convite para abrir o aplicativo, que o sistema monta sozinho. Em branco, vale um texto padrão. O e-mail sai pela fila de envio, dez por minuto, e nunca leva situação nem nota.',
        ],
        [
            'nome' => 'Situação',
            'como' => 'Rascunho (formulário indisponível), Publicado (submissão aberta a quem estiver no prazo) ou Encerrado.',
        ],
    ],
    'conceitos' => ['sigilo_anonimato'],
];
