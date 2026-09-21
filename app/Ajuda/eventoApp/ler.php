<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Ler código',
    'resumo' => 'Confirma se um código de credenciamento (de outra pessoa) pertence a uma inscrição válida neste evento, pela câmera, quando o navegador suportar, ou por digitação manual.',
    'operacoes' => [
        [
            'nome' => '"Usar a câmera"',
            'como' => 'Só aparece em navegadores com suporte à leitura automática de QR (Quick Response) (Chrome/Edge). Pede permissão de câmera e, ao apontar para o QR de outra pessoa, valida sozinho. Em Safari e Firefox esse botão não aparece; a leitura é sempre pelo campo abaixo.',
        ],
        [
            'nome' => 'Campo de digitação manual',
            'como' => 'Digite o código de 6 caracteres mostrado no crachá da outra pessoa e toque em "Validar". Funciona em qualquer navegador, inclusive como alternativa se a leitura da câmera falhar.',
        ],
        [
            'nome' => 'Resultado',
            'como' => 'Mostra o nome da pessoa e o evento quando o código é válido, ou "Código não encontrado" quando não é. Muitas tentativas erradas em seguida bloqueiam novas tentativas por alguns minutos.',
        ],
        [
            'nome' => 'Sino de notificações',
            'como' => 'Mostra avisos como a confirmação da sua inscrição. Um número vermelho indica quantas ainda não foram vistas.',
        ],
    ],
    'conceitos' => [],
];
