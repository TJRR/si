<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Meu trabalho',
    'resumo' => 'Acompanhamento do trabalho em que você é autor ou coautor: número do protocolo, situação atual, dados de autoria e, depois que a organização publica o resultado, a pontuação. A tela é somente para leitura, com uma exceção: o envio da versão final para os Anais, quando a organização abre o prazo.',
    'operacoes' => [
        [
            'nome' => 'Protocolo',
            'como' => 'Número que identifica o trabalho, o mesmo informado na tela e no e-mail depois do envio. Use-o ao falar com a organização do evento.',
        ],
        [
            'nome' => 'Situação',
            'como' => 'Submetido (aguardando avaliação), Desclassificado (com o motivo, se houver), Aprovado ou Reprovado. As duas últimas só aparecem depois que a organização publica o resultado; até lá o trabalho continua como Submetido, mesmo já avaliado. A desclassificação aparece assim que é registrada.',
        ],
        [
            'nome' => 'Resultado da avaliação',
            'como' => 'Depois da publicação, o cartão mostra a situação final e, para o trabalho aprovado, se ele foi selecionado para apresentação. Conforme a configuração do evento, mostra também a nota final, a posição entre os trabalhos avaliados e a média de cada critério. Você é avisado por e-mail e pelo sino do aplicativo quando o resultado sai.',
            'observacao' => 'A nota de cada avaliador nunca aparece, e os avaliadores não são identificados. Se a organização reabrir o resultado para uma correção, o trabalho volta a aparecer como Submetido até uma nova publicação.',
        ],
        [
            'nome' => 'Versão final para os Anais',
            'como' => 'Depois da publicação do resultado, se o seu trabalho consta nos Anais e a organização abriu o prazo, aparece o quadro "Versão final para os Anais". O autor principal envia ali o PDF final do trabalho, que entra no volume dos Anais, e pode trocar o arquivo até o fim do prazo. Os coautores veem só a situação do envio.',
            'observacao' => 'Se o arquivo for recusado, salve-o de novo como PDF/A-1b e envie outra vez. Não é preciso numerar as páginas: o número de cada página é colocado no volume.',
        ],
        [
            'nome' => 'Publicado nos Anais',
            'como' => 'Quando a organização publica os Anais do evento e o seu trabalho consta neles, aparece a marca "Publicado nos Anais" ao lado da situação, com o atalho "Abrir os Anais", que abre o PDF do volume em outra aba. Você também é avisado por e-mail e pelo sino do aplicativo.',
        ],
    ],
    'conceitos' => [],
];
