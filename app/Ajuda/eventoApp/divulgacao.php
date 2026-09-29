<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Divulgação',
    'resumo' => 'Ganhe pontos divulgando o evento nas suas redes sociais e acompanhando os canais do Tribunal. Você envia a comprovação e os pontos entram na hora.',
    'operacoes' => [
        [
            'nome' => 'Antes de enviar',
            'como' => 'Informe o seu perfil daquela rede em Meu Perfil. A comprovação é conferida contra a conta que você cadastrou, então sem ela o envio é recusado.',
        ],
        [
            'nome' => 'Publiquei sobre o evento',
            'como' => 'Escolha a rede, cole o endereço da sua publicação ou envie a imagem da tela, conforme o que a rede aceita neste evento. Cada publicação vale os pontos indicados em "O que vale neste evento".',
        ],
        [
            'nome' => 'Passei a acompanhar o canal do Tribunal',
            'como' => 'Vale uma única vez por rede social. Acompanhando mais de uma, você soma os pontos de cada uma.',
        ],
        [
            'nome' => 'Situação de cada comprovação',
            'como' => 'Verde mostra os pontos que você recebeu. Laranja é comprovação registrada sem pontos, quando você já atingiu o limite daquela rede. Vermelho é comprovação anulada pela organização, com o motivo escrito ali.',
            'observacao' => 'Anulada, a comprovação deixa de pontuar e a vaga do limite volta a ficar disponível, mas aquela mesma prova não pode ser enviada de novo.',
        ],
        [
            'nome' => 'A imagem que você envia',
            'como' => 'Fica guardada em área restrita e é vista pela organização do evento para a conferência. Evite enviar captura com informação de outras pessoas que você não queira compartilhar.',
            'observacao' => 'Depois do evento, a organização apaga as imagens. Os seus pontos continuam.',
        ],
        [
            'nome' => 'Pontos de cada ação',
            'como' => 'São os do momento do envio. Se a organização mudar os valores depois, o que você já ganhou não muda.',
        ],
    ],
    'conceitos' => [],
];
