<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Configurações de Divulgação',
    'resumo' => 'Liga ou desliga a divulgação neste evento, define o período em que as comprovações valem e escolhe quais redes sociais valem, com quanto cada ação vale, o que serve de prova e os limites.',
    'operacoes' => [
        [
            'nome' => 'Acrescentar rede',
            'como' => 'A tela começa vazia: escolha no seletor a rede que vale neste evento e acrescente. Só as redes escolhidas aparecem aqui e no aplicativo. O seletor some quando todas já foram acrescentadas.',
            'observacao' => 'A rede entra desligada: marque o que ela aceita e quanto vale, e salve.',
        ],
        [
            'nome' => 'Retirar',
            'icone' => 'remover',
            'como' => 'O ✕ ao lado do nome tira a rede do evento: ela some desta tela e deixa de aparecer para o participante.',
            'observacao' => 'Pontos, limites e canal ficam guardados, então acrescentar a rede de novo a traz como estava. As comprovações já enviadas naquela rede continuam na aba "Comprovações", com os pontos daquele momento.',
        ],
        [
            'nome' => 'Ativar a divulgação neste evento',
            'como' => 'Desativada, o aplicativo não oferece o envio de comprovação e nenhuma pontuação nova é registrada. As comprovações já enviadas continuam visíveis para quem as enviou.',
        ],
        [
            'nome' => 'A partir de / Até',
            'como' => 'Período em que as comprovações valem. Cada campo limita por conta própria, e em branco aquele lado não limita nada: com os dois em branco, a divulgação fica aberta enquanto estiver ativada, e quem decide liberar e fechar é a marca acima. Preencha para aceitar a divulgação feita antes da abertura do evento ou para dar prazo de envio depois do encerramento.',
            'observacao' => 'O último dia conta inteiro. O sistema nunca assume um período que você não escreveu.',
        ],
        [
            'nome' => 'Apagar as imagens automaticamente',
            'como' => 'Quantos dias depois do último dia do evento o sistema apaga sozinho as imagens de comprovação deste evento. A rotina roda uma vez por dia. As comprovações e os pontos continuam registrados; só a imagem deixa de existir.',
            'observacao' => 'Em branco, nada é apagado automaticamente, e as imagens ficam até alguém usar "Apagar imagens" na tela Comprovações.',
        ],
        [
            'nome' => 'Cada rede social',
            'como' => 'Para cada rede acrescentada, escolha se ela aceita comprovação de publicação, de passar a seguir o canal, ou as duas, e quanto cada uma vale. Cada rede mostra só o que aceita: Spotify e Flickr só seguir; WhatsApp e "Qualquer rede" só publicação. Rede acrescentada e sem nada marcado não aparece para o participante.',
            'observacao' => 'A publicação no Instagram, Facebook, YouTube, LinkedIn e X exige a rede cadastrada em Meu Perfil, e a do WhatsApp, o telefone marcado como WhatsApp. O TikTok não é campo do perfil, então a publicação nele não exige conta cadastrada.',
        ],
        [
            'nome' => 'Qualquer rede',
            'como' => 'Publicação em qualquer rede, inclusive a rede interna de outro órgão: basta a imagem da tela, sem conta cadastrada e sem conferência da rede. A pessoa pode informar o nome da rede.',
            'observacao' => 'Quem publica no LinkedIn também pode enviar por aqui, com o valor desta forma e sem o limite do LinkedIn. É o que a dinâmica de pontos pede ("a rede não importa"); a auditoria com anulação continua disponível.',
        ],
        [
            'nome' => 'Nome e endereço do canal',
            'como' => 'Nas redes que aceitam seguir, o nome do canal indicado pela organização (por exemplo, o canal oficial do evento naquela rede) e o endereço dele. O participante vê "Siga (nome) no (rede)", e o endereço vira atalho clicável quando começa por http ou https. Seguir não exige a conta da rede no perfil da pessoa.',
        ],
        [
            'nome' => 'O que a pessoa envia como prova',
            'como' => 'Imagem da tela, endereço da publicação ou os dois; WhatsApp e "Qualquer rede" aceitam só a imagem. O sistema confere que o endereço é mesmo daquela rede, e que a pessoa cadastrou a conta dela em Meu Perfil (no WhatsApp, o telefone marcado como WhatsApp). Não há conferência da prova antes do crédito.',
            'observacao' => 'Para seguir um canal, deixe a prova em "só a imagem": com endereço, todos colariam o mesmo endereço do canal, e só o primeiro pontuaria, porque cada endereço vale uma vez no evento.',
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
    ],
    'conceitos' => ['permissao_suporte_admin'],
];
