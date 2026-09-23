<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Atividades',
    'resumo' => 'Cursos, palestras e seminários deste evento. Cada atividade tem período, local e vagas próprios, e decide por conta própria se exige inscrição e se emite certificado. Quem está inscrito no evento pode ou não se inscrever em cada atividade dele.',
    'operacoes' => [
        ['nome' => '+ Nova atividade', 'como' => 'Abre o formulário de uma atividade nova (Dados Gerais).'],
        ['nome' => 'Editar', 'icone' => 'editar', 'como' => 'Abre a atividade para edição.'],
        ['nome' => 'Remover', 'icone' => 'remover', 'como' => 'Só funciona se a atividade ainda não tiver inscrições.'],
        ['nome' => 'Inscritos', 'icone' => 'inscritos', 'como' => 'Lista de quem se inscreveu, separada em confirmados e lista de espera.'],
        ['nome' => 'Coluna "Vagas"', 'como' => 'Mostra "confirmadas / limite" quando há limite configurado, "Ilimitada" quando a atividade exige inscrição sem limite, ou um traço simples quando não exige inscrição.'],
    ],
    'conceitos' => [],
];
