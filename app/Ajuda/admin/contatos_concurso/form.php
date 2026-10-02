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
            'nome' => 'Nome do organizador para assinatura dos e-mails',
            'como' => 'É o nome do remetente de todos os e-mails que o sistema envia, do Concurso e dos Eventos, e também assina os e-mails automáticos, acima do e-mail e do telefone de contato. A troca vale a partir do envio seguinte.',
            'observacao' => 'Em branco, os e-mails saem com o nome de remetente padrão e assinados só com os canais de contato preenchidos.',
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
