<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Perguntas da pesquisa',
    'resumo' => 'Cadastro e ordem das perguntas que o participante responde.',
    'operacoes' => [
        [
            'nome' => 'Nova pergunta',
            'como' => 'Escreva a pergunta e escolha o tipo de resposta: escala de 1 a 5, lista de opções com uma escolha, múltipla escolha ou texto livre.',
        ],
        [
            'nome' => 'Ordem',
            'como' => 'A ordem desta lista é a ordem em que as perguntas aparecem para quem responde.',
        ],
        [
            'nome' => 'Pergunta já respondida',
            'como' => 'Fica travada no enunciado, no tipo e nas opções. A resposta guarda a posição da opção escolhida, então mexer na lista mudaria, em silêncio, o significado do que já foi respondido. O texto de ajuda, a obrigatoriedade e a situação continuam editáveis.',
        ],
        [
            'nome' => 'Remover',
            'como' => 'Pergunta nunca respondida é removida de verdade. Depois da primeira resposta, "remover" passa a desativar: ela sai do formulário e continua no resultado, para que os números já apurados não desapareçam.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin', 'reordenar_arraste', 'pesquisa_anonima'],
];
