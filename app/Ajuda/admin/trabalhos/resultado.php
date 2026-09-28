<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Resultado de Trabalhos',
    'resumo' => 'Mostra a prévia do resultado, calculada na hora a partir das notas lançadas pelos avaliadores, e publica esse resultado para os autores. Só depois da publicação o autor vê aprovado, reprovado, nota, posição e média por critério.',
    'operacoes' => [
        [
            'nome' => 'Prévia do resultado',
            'como' => 'Combina as notas dos avaliadores conforme configurado (média aritmética ou mediana), aplica a nota de corte, resolve empate pela cascata de regras de desempate e indica quem fica selecionado para apresentação, conforme a regra configurada (todos os aprovados, número fixo ou percentual). Nada disso vale para os autores enquanto o resultado não for publicado.',
        ],
        [
            'nome' => 'Coluna Desempate',
            'como' => 'Preenchida só nas linhas que empataram na nota final com o trabalho de cima, dizendo qual critério decidiu.',
        ],
        [
            'nome' => 'Publicar resultado',
            'como' => 'Grava em cada trabalho a situação, a seleção, a nota final, a posição e a média por critério, marca o resultado como publicado e avisa os autores por e-mail (pela fila de envio) e pelo sino do aplicativo. O que os autores veem é o que ficou gravado nesse momento.',
            'observacao' => 'Trabalho sem nenhuma nota lançada é gravado como reprovado, e a confirmação informa quantos são. Trabalho desclassificado fica fora da classificação e dos avisos. Só o Administrador publica.',
        ],
        [
            'nome' => 'Reabrir resultado',
            'como' => 'Tira o resultado do ar: os autores voltam a ver o trabalho como Submetido. Serve para corrigir uma nota ou uma desclassificação e publicar de novo, o que recalcula tudo do zero e avisa os autores outra vez.',
            'observacao' => 'Depois de publicado, mudar uma nota altera a prévia, mas não altera o que os autores veem: só uma nova publicação faz isso.',
        ],
        [
            'nome' => 'Posição e classificação',
            'como' => 'Lista todos os trabalhos avaliados, ordenados pela nota final. A posição que o autor vê é a ordem desta lista no momento da publicação.',
        ],
    ],
    'conceitos' => [],
];
