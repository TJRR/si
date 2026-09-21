<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Confirmar presença',
    'resumo' => 'Confirma sua presença numa atividade apontando a câmera para o código fixo afixado no espaço onde ela acontece, pela câmera, quando o navegador suportar, ou por digitação manual. O mesmo leitor serve para qualquer atividade: a atividade é identificada pelo próprio código lido.',
    'operacoes' => [
        [
            'nome' => '"Usar a câmera"',
            'como' => 'Só aparece em navegadores com suporte à leitura automática de QR (Quick Response) (Chrome/Edge). Pede permissão de câmera e, ao apontar para o cartaz afixado na sala, confirma sozinha. Em Safari e Firefox esse botão não aparece; a confirmação é sempre pelo campo abaixo.',
        ],
        [
            'nome' => 'Campo de digitação manual',
            'como' => 'Digite o código de 6 caracteres mostrado no cartaz da atividade e toque em "Validar". Funciona em qualquer navegador, inclusive como alternativa se a leitura da câmera falhar.',
        ],
        [
            'nome' => 'Resultado',
            'como' => 'Confirma o nome da atividade quando o código é válido. Se a atividade exigir inscrição prévia e você não estiver inscrito(a) e confirmado(a) nela, ou se a confirmação ainda não estiver disponível (muito antes do início), mostra a mensagem explicando o motivo. Ler o mesmo código de novo não é erro; só confirma que a presença já estava registrada. Muitas tentativas erradas em seguida bloqueiam novas tentativas por alguns minutos.',
        ],
    ],
    'conceitos' => [],
];
