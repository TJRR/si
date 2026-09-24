<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Carrossel do Evento',
    'resumo' => 'Carrossel do topo da página pública deste evento, independente do carrossel do Concurso e de qualquer outro evento.',
    'operacoes' => [
        ['nome' => '+ Novo quadro', 'como' => 'Abre o formulário de um novo quadro do carrossel deste evento.'],
        ['nome' => 'Editar / Remover', 'icone' => 'editar', 'como' => 'Remover também apaga os arquivos da imagem do quadro (computador e celular).'],
        ['nome' => 'Reordenar', 'como' => 'Ver conceito "Reordenar por arraste" abaixo: aqui define a ordem de exibição no carrossel deste evento, sem afetar o Concurso nem outros eventos.'],
    ],
    'conceitos' => ['reordenar_arraste'],
];
