<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Apresentações de trabalho',
    'resumo' => 'Marcação de quem de fato apresentou o trabalho no evento. Só os autores de trabalho marcado recebem o certificado de apresentação.',
    'operacoes' => [
        [
            'nome' => 'Quem aparece na lista',
            'como' => 'Os trabalhos selecionados para apresentação, com o resultado já publicado. A seleção acontece em Trabalhos, Resultado, e esta tela não altera nada dela.',
        ],
        [
            'nome' => 'Marcar como apresentados',
            'como' => 'Selecione as linhas e use o botão. A observação é opcional e fica guardada junto com a marca, útil para registrar uma substituição de autor ou uma justificativa aceita pela organização.',
        ],
        [
            'nome' => 'Quem recebe o certificado',
            'como' => 'Todos os autores do trabalho marcado, e não apenas quem ficou junto ao cartaz de exposição. Coautor que nunca teve conta no sistema também recebe, e o documento dele sai pela aba "Quem tem direito".',
        ],
        [
            'nome' => 'Retirar a marca',
            'como' => 'Tira o direito ao certificado de apresentação dos autores daquele trabalho. Certificado já emitido continua valendo: o documento é guardado como foi emitido, e para desfazer uma emissão é preciso cancelá-la na aba "Emitidos".',
        ],
        [
            'nome' => 'Sem marca nenhuma',
            'como' => 'Nenhum certificado de apresentação é emitido. A ausência de marca é a situação de partida: o sistema nunca supõe que um trabalho foi apresentado.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin'],
];
