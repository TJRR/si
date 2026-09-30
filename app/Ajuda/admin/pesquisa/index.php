<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Resultado da pesquisa de satisfação',
    'resumo' => 'Quantas pessoas responderam e o resultado de cada pergunta, sempre sem identificar quem respondeu o quê.',
    'operacoes' => [
        [
            'nome' => 'Convidar a responder',
            'como' => 'Avisa, pelo sino do aplicativo e por correio eletrônico, só quem ainda não respondeu. O aviso no aplicativo chega na hora; as mensagens saem na mesma fila do evento, dez por minuto, então algumas centenas de inscritos levam perto de uma hora.',
            'observacao' => 'Enquanto a fila do convite anterior não termina, um convite novo é recusado, para não criar duas campanhas.',
        ],
        [
            'nome' => 'Números por pergunta',
            'como' => 'A escala mostra a média e quantas respostas teve cada nota; as perguntas de opção mostram a contagem e o percentual de cada uma; as de texto mostram as respostas escritas, em ordem aleatória.',
        ],
        [
            'nome' => 'Mínimo de respostas',
            'como' => 'Abaixo de cinco respostas, a tela mostra apenas o total. Com poucas pessoas, o próprio conteúdo das respostas identificaria quem escreveu.',
        ],
        [
            'nome' => 'Exportar respostas',
            'como' => 'Gera a planilha com uma linha por resposta enviada e uma coluna por pergunta, sem nome e sem horário, em ordem aleatória.',
        ],
        [
            'nome' => 'Pontos de quem responde',
            'como' => 'Vêm de um bônus cadastrado do tipo "responder à pesquisa de satisfação", na aba Bônus. Sem esse bônus, a pesquisa funciona igual, apenas sem creditar pontos, e a tela avisa.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin', 'pesquisa_anonima'],
];
