<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Meu Perfil',
    'resumo' => 'Seus dados pessoais e a escolha do que outras pessoas do evento veem de você depois de uma conexão. Vale para todo o sistema, não só para este evento.',
    'operacoes' => [
        [
            'nome' => 'Foto e nome',
            'como' => 'A foto aceita JPG, PNG, WEBP ou GIF, até 4 MB. O endereço de correio eletrônico é o da sua conta e não pode ser alterado.',
        ],
        [
            'nome' => 'Meus dados',
            'como' => 'Documento, cargo, categoria profissional, órgão de origem e minicurrículo. São os mesmos dados usados quando você é designado para facilitar uma atividade, para não precisar digitar tudo de novo.',
            'observacao' => 'Se você já enviou algum trabalho, corrigir o CPF aqui corrige também nos trabalhos enviados.',
        ],
        [
            'nome' => 'Meus contatos',
            'como' => 'Telefone, com a marca de que ele recebe mensagem no WhatsApp, e as suas redes sociais. Em cada rede, informe apenas o seu nome de usuário: o começo do endereço aparece ao lado do campo e o sistema monta o resto. Quem preferir pode colar o endereço completo do próprio perfil, com ou sem o começo.',
            'observacao' => 'O endereço colado precisa ser da própria rede: um endereço do LinkedIn colado no campo do Instagram é recusado, porque esses endereços aparecem para quem se conecta com você.',
        ],
        [
            'nome' => 'O que compartilho nas conexões',
            'como' => 'Marque o que quer mostrar a quem se conectar com você no evento. Nome e endereço de correio eletrônico aparecem sempre; o resto só com a sua autorização.',
            'observacao' => 'Você pode mudar a qualquer momento, e a mudança vale também para as conexões que já aconteceram. Esconder depois vale para o sistema, não para a memória de quem já viu.',
        ],
    ],
    'conceitos' => [],
];
