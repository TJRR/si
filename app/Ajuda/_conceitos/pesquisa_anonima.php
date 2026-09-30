<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Pesquisa anônima com pontos nominais',
    'texto' => 'A pesquisa de satisfação premia quem responde, e ao mesmo tempo guarda as respostas sem dono. As duas coisas ficam em lugares separados: de um lado, o registro de que a pessoa respondeu, com o nome dela, que é o que credita os pontos e o que impede responder duas vezes; do outro, as respostas, sem nenhum campo que aponte para alguém. Não existe ligação entre as duas, então nenhuma tela, consulta ou exportação do sistema consegue dizer o que uma pessoa específica respondeu, nem para o Administrador. Por isso a resposta também não pode ser corrigida depois: nem o sistema encontra as suas para trocar. Duas consequências práticas: os números por pergunta só aparecem a partir de um mínimo de respostas, porque com poucas pessoas o próprio texto escrito identificaria quem respondeu; e a exportação sai em ordem aleatória, sem nome e sem horário.',
];
