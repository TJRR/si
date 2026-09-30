<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Cadastro de pergunta',
    'resumo' => 'Define uma pergunta da pesquisa de satisfação e o tipo de resposta que ela aceita.',
    'operacoes' => [
        [
            'nome' => 'Escala de 1 a 5',
            'como' => 'A pessoa escolhe uma nota. Os nomes dos extremos são opcionais (por exemplo, "Muito ruim" e "Muito bom"); em branco, aparecem só os números. É o tipo que dá média e distribuição no resultado.',
        ],
        [
            'nome' => 'Lista de opções, uma escolha',
            'como' => 'A pessoa escolhe uma única opção. Escreva uma opção por linha, na ordem em que devem aparecer.',
        ],
        [
            'nome' => 'Múltipla escolha',
            'como' => 'A pessoa marca quantas opções quiser. No resultado, cada opção é contada em separado, e o percentual é sobre o total de pessoas que responderam àquela pergunta.',
        ],
        [
            'nome' => 'Texto livre',
            'como' => 'Campo aberto, com limite de dois mil caracteres. As respostas aparecem no resultado em ordem aleatória, sem nome.',
        ],
        [
            'nome' => 'Obrigatória',
            'como' => 'Pergunta obrigatória impede o envio enquanto não for respondida. As demais podem ficar em branco.',
        ],
        [
            'nome' => 'Pergunta ativa',
            'como' => 'Desativada, sai do formulário de quem ainda vai responder e continua no resultado.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin', 'pesquisa_anonima'],
];
