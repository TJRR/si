<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Divulgação',
    'resumo' => 'Ganhe pontos divulgando o evento nas redes sociais e seguindo os canais indicados pela organização. Você envia a comprovação e os pontos entram na hora, sem conferência prévia; a organização pode auditar depois.',
    'operacoes' => [
        [
            'nome' => 'Quando vale',
            'como' => 'A organização define o período em que as comprovações valem. Quando há um período, a tela diz qual é; quando não há, vale enquanto a divulgação estiver aberta neste evento.',
        ],
        [
            'nome' => 'Antes de enviar',
            'como' => 'Publicação numa rede específica (Instagram, Facebook, YouTube, LinkedIn ou X) precisa ser da conta que você cadastrou em Meu Perfil. No WhatsApp, a conta é o telefone do seu perfil marcado como WhatsApp. Publicação no TikTok e seguir um canal não exigem conta cadastrada.',
        ],
        [
            'nome' => 'O que o formulário pede',
            'como' => 'O formulário pergunta só o que tem mais de uma resposta possível. Quando o evento liga uma rede só, ou aceita uma ação só, a tela já diz o que vale e não pergunta nada disso; e aparece só o campo da prova que aquela rede aceita, o endereço ou a imagem da tela.',
        ],
        [
            'nome' => 'Publiquei sobre o evento',
            'como' => 'Cole o endereço da sua publicação ou envie a imagem da tela, conforme o que a rede aceita neste evento. Cada publicação vale os pontos indicados em "O que vale neste evento".',
        ],
        [
            'nome' => 'Qualquer rede',
            'como' => 'Para publicar em qualquer rede, inclusive a rede interna de outro órgão: basta a imagem da tela, sem conta cadastrada. Se quiser, informe em que rede publicou, até 60 caracteres, o que ajuda a organização a entender a sua comprovação.',
        ],
        [
            'nome' => 'Passei a seguir o canal indicado',
            'como' => 'Vale uma única vez por rede. Seguindo o canal em mais de uma rede, você soma os pontos de cada uma. O nome e o endereço do canal aparecem em "O que vale neste evento".',
        ],
        [
            'nome' => 'Situação de cada comprovação',
            'como' => 'Verde mostra os pontos que você recebeu. Laranja é comprovação registrada sem pontos, quando você já atingiu o limite daquela rede. Vermelho é comprovação anulada pela organização, com o motivo escrito ali.',
            'observacao' => 'Anulada, a comprovação deixa de pontuar e a vaga do limite volta a ficar disponível, mas aquela mesma prova não pode ser enviada de novo.',
        ],
        [
            'nome' => 'A imagem que você envia',
            'como' => 'Fica guardada em área restrita e é vista pela organização do evento na auditoria. Evite enviar captura com informação de outras pessoas que você não queira compartilhar.',
            'observacao' => 'Depois do evento, a organização apaga as imagens. Os seus pontos continuam.',
        ],
        [
            'nome' => 'Pontos de cada ação',
            'como' => 'São os do momento do envio. Se a organização mudar os valores depois, o que você já ganhou não muda. Depois do encerramento da gincana, as comprovações deixam de ser aceitas.',
        ],
    ],
    'conceitos' => [],
];
