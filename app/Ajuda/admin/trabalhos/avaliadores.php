<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Avaliadores de Trabalhos',
    'resumo' => 'Pool de pessoas autorizadas a avaliar Trabalhos deste evento - avaliador avulso, sem nenhuma relação com o perfil "avaliador" do Concurso.',
    'operacoes' => [
        [
            'nome' => 'Convidar',
            'como' => 'Nome e e-mail. Se o e-mail já tiver conta no sistema, a busca em tempo real preenche nome/e-mail automaticamente. Convite bloqueado se esse e-mail já for autor (principal ou coautor) de um trabalho deste evento.',
            'observacao' => 'Conta nova nasce aprovada, sem fila de análise, com e-mail de convite trazendo um endereço para definir senha. Conta já existente só recebe o aviso de que ganhou autorização para avaliar este evento, sem esse endereço de definir senha.',
        ],
        [
            'nome' => 'Remover',
            'icone' => 'remover',
            'como' => 'Tira a pessoa do pool geral de avaliadores deste evento - não desfaz designações já feitas a trabalhos específicos (ver "Trabalhos recebidos").',
        ],
    ],
    'conceitos' => [],
];
