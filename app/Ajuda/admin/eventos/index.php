<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Eventos',
    'resumo' => 'Cadastro de eventos da Semana de Inovação. É uma entidade nova, sem nenhum vínculo com o concurso "Prêmio de Inovação" em andamento. Tem a mesma situação estrutural de Concurso: múltiplos eventos possíveis, cada um com sua própria árvore de configuração.',
    'operacoes' => [
        ['nome' => '+ Novo evento', 'como' => 'Abre o formulário de um evento novo (Dados Gerais).'],
        ['nome' => 'Editar', 'icone' => 'editar', 'como' => 'Abre a árvore de configuração do evento.'],
        ['nome' => 'Remover', 'icone' => 'remover', 'como' => 'Só funciona se o evento ainda não tiver inscrições.'],
        ['nome' => 'Inscritos', 'como' => 'Lista de inscritos naquele evento, com exportação e credenciamento.'],
    ],
    'conceitos' => [],
];
