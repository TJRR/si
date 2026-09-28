<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Meus trabalhos',
    'resumo' => 'Lista de todo trabalho em que você consta como autor ou coautor, em qualquer evento, com o número do protocolo e a situação atual de cada um.',
    'operacoes' => [
        [
            'nome' => 'Situação',
            'como' => 'Submetido (aguardando avaliação), Desclassificado, Aprovado ou Reprovado. Aprovado e Reprovado só aparecem depois que a organização publica o resultado; até lá o trabalho continua como Submetido. Ao publicar, você é avisado por e-mail e pelo sino do aplicativo.',
        ],
        [
            'nome' => 'Publicado nos Anais',
            'como' => 'Marca que aparece no trabalho aprovado quando a organização publica os Anais do evento e o trabalho consta neles. O atalho "Abrir os Anais" abre o PDF do volume em outra aba.',
        ],
        [
            'nome' => 'Ver detalhes',
            'como' => 'Abre a situação completa daquele trabalho.',
        ],
        [
            'nome' => 'Trabalhos em que você é coautor',
            'como' => 'Aparecem com a marca "(você é coautor)". O coautor acompanha a situação do trabalho, somente para leitura: quem envia e corrige a submissão é o autor principal.',
        ],
    ],
    'conceitos' => [],
];
