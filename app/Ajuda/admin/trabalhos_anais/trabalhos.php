<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Trabalhos nos Anais',
    'resumo' => 'Lista dos trabalhos aprovados, com a marcação de quais constam nos Anais. Todos vêm marcados; desmarque quem não deve constar (por exemplo, trabalho não apresentado no evento). A lista define quais trabalhos entram no volume montado pelo sistema (aba Montagem dos Anais), orienta quem monta o PDF fora do sistema e define quem recebe o aviso de publicação e a indicação "Publicado nos Anais" que o autor vê no aplicativo.',
    'operacoes' => [
        [
            'nome' => 'Consta nos Anais',
            'como' => 'Caixa de marcação de cada trabalho. Desmarcada, o trabalho sai da lista, não recebe o aviso de publicação e não mostra a indicação "Publicado nos Anais" para o autor.',
            'observacao' => 'A lista só aparece depois de publicado o resultado de Trabalhos, e só traz trabalhos aprovados.',
        ],
        [
            'nome' => 'Motivo, se não constar',
            'como' => 'Texto livre e opcional, guardado junto da exclusão, para a equipe lembrar por que o trabalho ficou de fora.',
        ],
        [
            'nome' => 'Declarações',
            'como' => 'Alerta "Pendente" quando falta o aceite de alguma declaração obrigatória (Trabalhos, Declarações), por exemplo a autorização de publicação. Não impede o trabalho de constar, mas vale conferir antes de publicar os Anais.',
        ],
        [
            'nome' => 'Lista bloqueada com os Anais publicados',
            'como' => 'Enquanto os Anais estiverem publicados, a lista não muda, para o autor não ver a indicação mudar por baixo dele. Para alterá-la, despublique os Anais na aba Anais.',
        ],
    ],
    'conceitos' => [],
];
