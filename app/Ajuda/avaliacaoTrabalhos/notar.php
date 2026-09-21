<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Avaliar trabalho',
    'resumo' => 'Ficha do trabalho designado a você, com o conteúdo submetido e o lançamento de nota por critério.',
    'operacoes' => [
        [
            'nome' => 'Baixar documento',
            'como' => 'Baixa o arquivo submetido (sempre a versão sem identificação, quando a avaliação é às cegas). Passe o mouse sobre o botão para ver o formato do arquivo.',
        ],
        [
            'nome' => 'Consultar critérios',
            'como' => 'Botão ao lado do de baixar documento - abre um resumo do edital sobre os critérios de avaliação (nome, faixa de nota e como interpretar cada um), sem precisar sair da tela de avaliação.',
        ],
        [
            'nome' => 'Nota por critério',
            'como' => 'Digite o valor na caixa central (aceita vírgula) ou use os botões -/+ (passo de 0,1); a régua abaixo ajusta o mesmo valor de outro jeito. Sempre entre 0 e a nota máxima daquele critério, com duas casas decimais.',
        ],
        [
            'nome' => 'Salvar notas',
            'como' => 'Grava as notas preenchidas até agora; pode ser usado mais de uma vez para completar aos poucos.',
        ],
    ],
    'conceitos' => ['sigilo_anonimato'],
];
