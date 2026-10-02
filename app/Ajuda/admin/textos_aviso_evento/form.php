<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Texto de um aviso do Evento',
    'resumo' => 'Assunto e texto de um aviso por correio eletrônico do Evento. O que for salvo vale para todos os eventos, a partir do próximo envio. A assinatura com os dados de Contato entra sozinha no fim do texto.',
    'operacoes' => [
        [
            'nome' => 'Palavras-chave',
            'como' => 'Escreva a palavra-chave entre colchetes duplos, como [[nome]] ou [[evento]], e o sistema troca pelo valor de cada envio. A tabela da tela mostra as palavras-chave deste aviso e onde cada uma pode ir.',
            'observacao' => 'Palavra-chave que não existe neste aviso impede o salvamento, e a tela diz qual é. As que trazem ligação ou parágrafo pronto só podem ir no texto, nunca no assunto.',
        ],
        [
            'nome' => 'Palavra-chave obrigatória',
            'como' => 'Nos convites para quem ainda não tem acesso, o texto precisa ter a ligação ou o endereço de definir a senha; no aviso de recebimento de trabalho, o parágrafo de acesso. Sem isso, quem recebe não consegue entrar, e a tela não salva.',
        ],
        [
            'nome' => 'Salvar texto',
            'como' => 'Grava o assunto e o texto. Os próximos envios deste aviso já usam o texto novo.',
        ],
        [
            'nome' => 'Voltar ao texto padrão',
            'como' => 'Apaga o texto próprio, e o aviso volta a sair com o texto padrão do sistema. Aparece só quando o aviso tem texto próprio.',
        ],
    ],
    'conceitos' => [],
];
