<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Banco global x escopo por edição',
    'texto' => 'Alguns conteúdos (ex.: perguntas de FAQ, itens da Biblioteca de Mídia) vivem num banco único, reaproveitável entre todas as edições do Prêmio, e são cadastrados uma vez só. Depois, cada edição escolhe quais itens desse banco ficam ativos e em que ordem — "ativar" nunca duplica o texto, só liga um item já existente àquela edição. Remover um item do banco global só é permitido se ele não estiver em uso por nenhuma edição no momento.',
];
