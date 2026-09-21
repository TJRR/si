<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Contato do Concurso',
    'resumo' => 'Dados de contato institucional exibidos no rodapé/seção de contato da página inicial.',
    'operacoes' => [
        [
            'nome' => 'E-mail / Telefone / WhatsApp / Endereço',
            'como' => 'Dados de contato exibidos publicamente.',
        ],
        [
            'nome' => 'Texto institucional',
            'como' => 'Editor rico.',
        ],
        [
            'nome' => 'Redes sociais',
            'como' => 'URLs. Só aparece o ícone de quem foi preenchido.',
        ],
        [
            'nome' => 'Exibir formulário nativo na página inicial',
            'como' => 'Caixa de seleção: ativa um formulário de contato na própria página inicial (em vez de só mostrar os dados de contato).',
        ],
        [
            'nome' => 'Ver mensagens recebidas',
            'como' => 'Leva à tela de mensagens enviadas pelo formulário nativo (só leitura, com hiperlink mailto: para responder por fora do sistema).',
        ],
    ],
    'conceitos' => [],
];
