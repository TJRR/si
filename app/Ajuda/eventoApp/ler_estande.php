<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Registrar visita a estande',
    'resumo' => 'Registra a sua visita a um estande do evento pelo código do cartaz afixado nele, pela câmera, quando o navegador permitir, ou por digitação.',
    'operacoes' => [
        [
            'nome' => '"Usar a câmera"',
            'como' => 'Só aparece em navegadores com leitura automática de QR (código de barras bidimensional), como Chrome e Edge. Pede permissão de câmera e, ao apontar para o cartaz, registra sozinho. Em Safari e Firefox o registro é sempre pelo campo abaixo.',
        ],
        [
            'nome' => 'Campo de digitação',
            'como' => 'Digite o código de 6 caracteres do cartaz e toque em "Validar".',
        ],
        [
            'nome' => 'Resultado',
            'como' => 'Mostra o nome do estande e os pontos ganhos. Ler de novo o código de um estande já visitado não soma pontos, só confirma a visita. Estande fora de funcionamento e estande que você mesmo representa não contam pontos. Muitas tentativas com código errado em seguida bloqueiam novas tentativas por alguns minutos.',
        ],
    ],
    'conceitos' => [],
];
