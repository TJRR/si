<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Configurações da pesquisa',
    'resumo' => 'Liga ou desliga a pesquisa, define quando ela fica aberta e os textos que o participante lê.',
    'operacoes' => [
        [
            'nome' => 'Ativar a pesquisa neste evento',
            'como' => 'Desativada, o botão some do aplicativo e nenhuma resposta nova é aceita. As respostas já enviadas continuam no resultado.',
        ],
        [
            'nome' => 'Abre em / Fecha em',
            'como' => 'Cada campo limita por conta própria, e em branco aquele lado não limita nada: com os dois em branco, a pesquisa fica aberta enquanto estiver ativada. A pesquisa costuma abrir no último dia e ficar aberta por alguns dias depois do encerramento, e para isso basta preencher as duas.',
            'observacao' => 'A comparação é por data, então o último dia conta inteiro.',
        ],
        [
            'nome' => 'Título',
            'como' => 'O nome que aparece no topo da tela do participante. Em branco, o sistema usa "Pesquisa de satisfação".',
        ],
        [
            'nome' => 'Texto de abertura',
            'como' => 'O que a pessoa lê antes das perguntas. O aviso de que as respostas ficam separadas do nome já aparece sempre, e não depende deste texto.',
        ],
        [
            'nome' => 'Assunto e corpo do convite',
            'como' => 'A mensagem por correio eletrônico disparada pelo botão "Convidar a responder", na aba Resultado. Em branco, o sistema envia um texto padrão.',
        ],
        [
            'nome' => 'Pontos de quem responde',
            'como' => 'Não ficam aqui: são um bônus cadastrado do tipo "responder à pesquisa de satisfação", na aba Bônus. Sem esse bônus, responder não credita nada, e a tela avisa.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin', 'pesquisa_anonima'],
];
