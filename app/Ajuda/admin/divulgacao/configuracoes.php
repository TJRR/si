<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Configurações de Divulgação',
    'resumo' => 'Liga ou desliga a divulgação neste evento, define o período em que as comprovações valem e, para cada rede social, quanto cada ação vale, o que serve de prova e os limites.',
    'operacoes' => [
        [
            'nome' => 'Ativar a divulgação neste evento',
            'como' => 'Desativada, o aplicativo não oferece o envio de comprovação e nenhuma pontuação nova é registrada. As comprovações já enviadas continuam visíveis para quem as enviou.',
        ],
        [
            'nome' => 'A partir de / Até',
            'como' => 'Período em que as comprovações valem. Em branco nos dois campos, valem as datas do evento. Preencha para aceitar a divulgação feita antes da abertura ou para dar prazo de envio depois do encerramento.',
            'observacao' => 'O último dia conta inteiro.',
        ],
        [
            'nome' => 'Cada rede social',
            'como' => 'Escolha se a rede aceita comprovação de publicação, de passar a acompanhar o canal, ou as duas, e quanto cada uma vale. Rede sem nenhuma das duas marcada não aparece para o participante.',
        ],
        [
            'nome' => 'O que a pessoa envia como prova',
            'como' => 'Imagem da tela, endereço da publicação ou os dois. O sistema confere que o endereço é mesmo daquela rede, e que a pessoa cadastrou o próprio perfil nela em Meu Perfil.',
        ],
        [
            'nome' => 'Limites por dia e no evento',
            'como' => 'Zero em um limite significa sem limite naquela contagem; os dois preenchidos valem juntos. Passado o limite, a comprovação continua sendo registrada, só que sem pontos, e a tela avisa isso à pessoa.',
            'observacao' => 'Passar a acompanhar não tem limite porque já pontua uma única vez por rede.',
        ],
        [
            'nome' => 'Quando a mudança vale',
            'como' => 'Pontos, limites e período valem só para as próximas comprovações: o que já foi creditado não muda, nem para mais nem para menos.',
        ],
        [
            'nome' => 'Apagar as imagens de comprovação',
            'como' => 'Disponível depois que o evento passa da própria data final. Exige digitar o nome do evento e não pode ser desfeito. Apaga só os arquivos: comprovações, pontos e a proteção contra reenvio da mesma imagem continuam valendo.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin'],
];
