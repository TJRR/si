<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Quem tem direito a certificado',
    'resumo' => 'Relação nominal de quem tem direito a certificado neste evento, com o que cada pessoa somou e quantos documentos já foram emitidos para ela. É daqui que sai a emissão em lote.',
    'operacoes' => [
        [
            'nome' => 'Como a lista é montada',
            'como' => 'Ela é apurada a cada abertura da tela, a partir das presenças, das designações de facilitador e das notas lançadas pelos avaliadores. Por isso mostra sempre a situação de agora, e não um retrato guardado. O que fica congelado é o certificado emitido, na aba "Emitidos".',
        ],
        [
            'nome' => 'Quem entra pela régua',
            'como' => 'Só quem tem inscrição no evento. A régua é cadastrada em "Configurações", e quem tem direito cumpre todas as exigências escritas: critério deixado em branco não conta. Com os quatro em branco, todo inscrito tem direito.',
        ],
        [
            'nome' => 'Quem entra pela designação',
            'como' => 'Quem conduziu atividade entra pela designação ativa, sem passar pela régua. Quem avaliou trabalhos entra se foi designado e de fato lançou nota. Nenhum dos dois precisa de inscrição no evento, e por isso nenhum dos dois tem presença registrada.',
        ],
        [
            'nome' => 'Carga horária',
            'como' => 'Soma a união dos horários das atividades com presença e das atividades conduzidas: duas atividades no mesmo horário contam uma vez, então o número nunca passa do tempo real do evento. Quem avaliou trabalhos tem somada a carga horária declarada para a função, que a avaliação acontece fora dos dias do evento.',
        ],
        [
            'nome' => 'Coluna "Documentos"',
            'como' => 'Mostra quantos dos documentos a que a pessoa tem direito já foram emitidos, e lista quais são. Com tudo emitido, a linha não entra mais na seleção.',
        ],
        [
            'nome' => 'Emitir',
            'como' => 'O botão de cada linha emite todos os documentos daquela pessoa. A seleção com "Emitir" emite os de várias pessoas de uma vez, até 50 documentos por chamada: acima disso, repita a operação. O documento é guardado como foi emitido e entregue igual em todo pedido seguinte.',
        ],
        [
            'nome' => 'Quando a emissão abre',
            'como' => 'Depois do último dia do evento, para todos, inclusive para a organização: antes disso a carga horária ainda pode mudar, e o documento nunca mais muda depois de emitido. Abrir a emissão para quem participou é uma segunda chave, em "Configurações", então a organização pode imprimir os documentos antes de liberar o aplicativo.',
        ],
        [
            'nome' => 'Coautor sem conta no sistema',
            'como' => 'Coautor de trabalho que nunca teve conta aparece na lista com um aviso, e o documento dele sai apenas por aqui: ele não tem aplicativo para retirar.',
        ],
        [
            'nome' => 'Exportar',
            'como' => 'Gera a relação filtrada em planilha, com nome, documento, condição, números apurados e quantos documentos já saíram.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin'],
];
