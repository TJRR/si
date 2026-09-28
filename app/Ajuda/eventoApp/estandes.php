<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Estandes',
    'resumo' => 'Estandes de expositores e de patrocinadores do evento. Ao visitar um estande, leia o código do cartaz dele para registrar a visita e ganhar os pontos. Cada estande conta uma vez por participante.',
    'operacoes' => [
        ['nome' => '"Registrar visita"', 'como' => 'Abre o leitor do código do cartaz do estande (câmera, quando o navegador permitir, ou digitação).'],
        ['nome' => 'Seus pontos em estandes', 'como' => 'Soma dos pontos das visitas já registradas, com a lista dos estandes visitados.'],
        ['nome' => 'Lista de estandes', 'como' => 'Estandes do evento, com os pontos de cada visita. Os já visitados aparecem marcados com os pontos ganhos.'],
    ],
    'conceitos' => [],
];
