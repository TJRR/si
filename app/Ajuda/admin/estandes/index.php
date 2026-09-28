<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Estandes do Evento',
    'resumo' => 'Estandes de expositores e de patrocinadores do evento. Cada estande tem um código de visita fixo, gerado pelo sistema e impresso no cartaz: o participante lê esse código no aplicativo do evento e ganha os pontos da visita, uma vez por estande. O Suporte vê tudo e imprime cartazes, mas só o Administrador altera.',
    'operacoes' => [
        ['nome' => '+ Novo estande', 'como' => 'Abre o cadastro de um estande novo. O código de visita é gerado ao salvar.'],
        ['nome' => 'Editar', 'icone' => 'editar', 'como' => 'Abre os dados do estande, o código de visita e o bloco do representante (convidar, substituir ou remover).'],
        ['nome' => 'Imprimir cartaz', 'como' => 'Abre o cartaz A4 do estande, com o QR e o código em texto, pronto para imprimir.'],
        [
            'nome' => 'Remover',
            'icone' => 'remover',
            'como' => 'Apaga o estande, o logotipo e o vínculo do representante.',
            'observacao' => 'Estande com visita registrada não pode ser removido, para não apagar pontos já ganhos: desmarque "Ativo" para tirá-lo do aplicativo e da página.',
        ],
        ['nome' => 'Reordenar', 'como' => 'Ver conceito "Reordenar por arraste" abaixo: define a ordem dos estandes no aplicativo e na página do evento.'],
    ],
    'conceitos' => ['reordenar_arraste', 'permissao_suporte_admin'],
];
