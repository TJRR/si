<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Resultado de etapa (público)',
    'resumo' => 'Ranking de uma etapa publicada, sem login. O nível de detalhe é escolhido pelo Administrador na configuração da etapa: nada, só a lista de classificados, ranking completo, ou ranking + material enviado pela equipe. Nenhum dado sensível aparece aqui, mesmo no modo mais aberto.',
    'operacoes' => [],
    'conceitos' => ['publicar_trava'],
];
