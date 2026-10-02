<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Meus certificados',
    'resumo' => 'Os certificados a que você tem direito neste evento. Cada um é emitido uma vez e fica guardado, então pode ser baixado quantas vezes você precisar.',
    'operacoes' => [
        [
            'nome' => 'Quando os certificados aparecem',
            'como' => 'Depois do último dia do evento e depois que a organização abre a emissão. Antes disso, a tela não é alcançável: a carga horária ainda pode mudar, e o documento é guardado exatamente como foi emitido.',
        ],
        [
            'nome' => 'Certificado de participação no evento',
            'como' => 'Depende das exigências que a organização escreveu. Quando você ainda não tem direito, a tela lista cada exigência com o seu número ao lado, para você saber o que falta.',
        ],
        [
            'nome' => 'Certificado de uma atividade',
            'como' => 'Sai nas atividades em que você confirmou presença e que a organização marcou para emitir certificado próprio. A carga horária é a duração da atividade.',
        ],
        [
            'nome' => 'Certificado de apresentação de trabalho',
            'como' => 'Sai para os autores de trabalho que a organização marcou como apresentado. Ele atesta a apresentação e não declara carga horária.',
        ],
        [
            'nome' => 'Carga horária',
            'como' => 'Soma a união dos horários das atividades: duas atividades no mesmo horário contam uma vez, então o número nunca passa do tempo que o evento teve.',
        ],
        [
            'nome' => 'Emitir e baixar',
            'como' => 'O primeiro toque emite e guarda o documento. Dali em diante, o botão passa a ser de baixar, e entrega sempre o mesmo arquivo, com o mesmo código de conferência.',
        ],
        [
            'nome' => 'Código de conferência',
            'como' => 'Vem impresso no pé do documento, junto com o endereço de uma página pública. Quem recebe o seu certificado digita o código ali e confirma que ele foi emitido por este sistema.',
        ],
        [
            'nome' => 'Certificado cancelado',
            'como' => 'Se a organização cancelar um documento, a tela informa a data e o motivo, e o arquivo deixa de ser entregue. Procure a organização do evento para entender o caso.',
        ],
        [
            'nome' => 'Quem conduziu atividade ou avaliou trabalho',
            'como' => 'Também tem certificado, mesmo sem inscrição no evento. O caminho fica em "Minhas facilitações" e na tela de avaliação de trabalhos.',
        ],
    ],
    'conceitos' => [],
];
