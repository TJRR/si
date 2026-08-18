<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Publicar trava, reabrir libera',
    'texto' => 'Em pelo menos quatro pontos do sistema (Resultado da Etapa, Resultado Final da Trilha, Homologação), "Publicar" faz duas coisas ao mesmo tempo: coloca a informação no ar numa página pública (sem login) e congela o que está por trás dela — depois de publicado, não é mais possível lançar ou alterar notas/homologações daquele ponto. "Reabrir" desfaz as duas coisas: tira a página do ar e libera edição de novo, apagando o que estava publicado (a prévia recalculada volta a valer). Sempre com confirmação, porque afeta o que o público vê imediatamente.',
];
