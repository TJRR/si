<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Seções da página do Evento',
    'resumo' => 'Esta lista é a página pública do evento, de cima para baixo: cada linha é uma seção, e a ordem aqui é a ordem que quem visita vê.',
    'operacoes' => [
        ['nome' => 'Arrastar', 'como' => 'Muda a posição da seção na página. Ver conceito "Reordenar por arraste" abaixo.'],
        ['nome' => 'Na página', 'como' => 'Desmarcado, a seção some da página sem ser apagada, e o conteúdo dela continua cadastrado.'],
        ['nome' => 'No menu', 'como' => 'Marcado, a seção vira item do menu do cabeçalho, com o rótulo informado ao lado, na mesma ordem da página.'],
        ['nome' => 'Âncora', 'como' => 'Nome do destino de rolagem da seção. Com a âncora "programacao", qualquer botão com o destino "#programacao" (nos quadros, nas faixas, nos blocos) leva até ela. Em branco, vale o nome automático mostrado em cinza. Letras sem acento, números e hífen; o sistema ajusta o que for digitado.'],
        ['nome' => 'Editar conteúdo', 'icone' => 'editar', 'como' => 'Abre o cadastro daquele componente. Carrossel, Faixas e Blocos de conteúdo têm telas próprias, alcançadas pelo mesmo ícone.'],
        ['nome' => 'Adicionar seção', 'como' => 'Cria uma seção nova e já abre o cadastro dela. O mesmo tipo pode entrar quantas vezes você quiser na mesma página.'],
    ],
    'conceitos' => ['reordenar_arraste'],
];
