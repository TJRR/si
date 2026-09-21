<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Submeter trabalho',
    'resumo' => 'Formulário de envio de um artigo ou resumo expandido para este evento.',
    'operacoes' => [
        [
            'nome' => 'Autor principal e coautores',
            'como' => 'CPF, nome, e-mail, cargo e órgão de origem de cada autor. O CPF e o cargo do autor principal, se já cadastrados em "Meu Perfil", vêm preenchidos automaticamente.',
        ],
        [
            'nome' => 'Forma de envio',
            'como' => 'Escolha entre digitar o texto direto na tela, informar um endereço eletrônico de documento externo, ou enviar um arquivo (editável ou em PDF), conforme o que estiver habilitado para este evento.',
        ],
        [
            'nome' => 'Duas versões (avaliação às cegas)',
            'como' => 'Quando este evento usa avaliação às cegas, é preciso enviar duas versões com o mesmo conteúdo: uma sem qualquer identificação de autoria (a que o avaliador vê) e outra completa, com os dados de autoria (usada só se o trabalho for aprovado).',
            'observacao' => 'No método "digitar o texto direto na tela", a ocultação de autoria é estrutural (os campos de nome/CPF ficam fora do texto), mas não impede a pessoa de se identificar dentro do próprio texto - por isso o cuidado é responsabilidade de quem escreve.',
        ],
    ],
    'conceitos' => ['sigilo_anonimato'],
];
