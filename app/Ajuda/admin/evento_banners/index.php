<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Faixas do Evento',
    'resumo' => 'Faixas empilháveis abaixo do carrossel, na página pública deste evento, independentes das Faixas do Concurso e de qualquer outro evento.',
    'operacoes' => [
        ['nome' => '+ Nova faixa', 'como' => 'Abre o formulário de uma nova faixa deste evento.'],
        ['nome' => 'Editar / Remover', 'icone' => 'editar', 'como' => 'Remover também apaga os arquivos da imagem da faixa (computador e celular).'],
        ['nome' => 'Reordenar', 'como' => 'Ver conceito "Reordenar por arraste" abaixo: aqui define a ordem de exibição na página deste evento, sem afetar o Concurso nem outros eventos.'],
    ],
    'conceitos' => ['reordenar_arraste'],
];
