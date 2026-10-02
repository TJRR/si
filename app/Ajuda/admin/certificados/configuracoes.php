<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Configurações dos certificados',
    'resumo' => 'A régua de quem tem direito, os textos dos três tipos de certificado, os planos de fundo e a abertura da emissão para quem participou.',
    'operacoes' => [
        [
            'nome' => 'As duas condições da emissão',
            'como' => 'A primeira é o fim do evento, e vale para todos, inclusive para a organização: até o último dia a carga horária ainda pode mudar, e o documento nunca muda depois de emitido. A segunda é a chave desta tela, e vale só para quem participou: com ela fechada, a organização emite e imprime, e o aplicativo não mostra caminho para certificado.',
            'observacao' => 'Fechar a chave depois não desfaz nada: quem já retirou o documento continua com ele, e a página pública continua confirmando.',
        ],
        [
            'nome' => 'Avisar quem ainda não foi avisado',
            'como' => 'Cria, para cada pessoa com certificado disponível e ainda sem aviso, um aviso no sino do aplicativo, com a lista dos certificados, e um e-mail pela fila de envio, que leva à tela de certificados. Ao abrir a emissão com o evento já terminado, a caixa de avisar vem marcada; depois, o botão da tela avisa quem passou a ter certificado novo.',
            'observacao' => 'O aviso só sai quando o certificado pode ser retirado de fato: depois do último dia do evento, com o módulo ligado e a emissão aberta. Cada certificado avisado fica registrado, então fechar e reabrir a emissão, ou mudar a régua, nunca avisa a mesma pessoa de novo pelo mesmo certificado. Coautor sem conta no sistema não recebe aviso, porque só alcança o documento pela organização.',
        ],
        [
            'nome' => 'Emitir certificados neste evento',
            'como' => 'Desligado, nenhuma emissão acontece e nenhuma tela de participante mostra certificado. Os documentos já emitidos continuam guardados e continuam sendo conferidos na página pública.',
        ],
        [
            'nome' => 'A régua de quem tem direito',
            'como' => 'Quatro critérios, todos opcionais: atividades diferentes com presença, dias diferentes, horas apuradas e credenciamento no local. Quem tem direito cumpre todos os que estiverem escritos; o que ficar em branco não conta. Com os quatro em branco, todo inscrito tem direito, e a tela diz isso por escrito.',
            'observacao' => 'A régua vale apenas para quem tem inscrição. Quem conduziu atividade ou avaliou trabalho entra pela própria designação.',
        ],
        [
            'nome' => 'Horas apuradas',
            'como' => 'A conta soma a união dos horários das atividades: duas atividades no mesmo horário contam uma vez. É isso que impede o documento de declarar mais horas do que o evento teve.',
        ],
        [
            'nome' => 'Exigir o credenciamento no local',
            'como' => 'Confira antes a janela de leitura em Gamificação, Credenciamento: se ela cobrir só o primeiro dia, quem chegou depois não alcança o certificado.',
        ],
        [
            'nome' => 'Carga horária da função de avaliador',
            'como' => 'Número em horas, declarado pela organização e igual para todos, porque a avaliação acontece fora dos dias do evento e não tem horário registrado. Em branco, o certificado de quem avaliou sai sem carga horária. Só recebe quem foi designado e de fato lançou nota.',
        ],
        [
            'nome' => 'Os três textos',
            'como' => 'Um por tipo de certificado. O editor abre com um texto modelo, pronto para alterar, e nada é gravado antes de salvar. Sem texto escrito, aquele tipo de certificado não é emitido: o sistema não inventa o conteúdo de um documento da instituição.',
        ],
        [
            'nome' => 'Palavras-chave',
            'como' => 'O seletor da barra do editor insere as palavras-chave que o sistema troca na emissão, como o nome da pessoa, o período do evento e a carga horária. Cada tipo tem as suas: o texto da atividade conhece o nome e o horário dela, o da apresentação conhece o título e a autoria do trabalho. Palavra-chave de outro tipo é recusada ao salvar, com o nome dela na mensagem.',
        ],
        [
            'nome' => 'Plano de fundo',
            'como' => '"Escolher imagem" abre a Biblioteca de mídia na pasta "Fundo Certificados", criada pelo sistema na primeira abertura desta tela, já com a arte que acompanha o sistema dentro. Na mesma janela é possível enviar uma arte nova, que fica escolhida ao concluir.',
            'observacao' => 'A arte precisa ter a proporção de uma folha A4 na horizontal.',
        ],
        [
            'nome' => 'Fundo sem imagem',
            'como' => 'O ícone ao lado do seletor deixa o fundo sem imagem e sem cor, e a folha sai branca. O seletor de cor pinta a folha de uma cor só. Imagem e cor nunca valem juntas: escolher uma troca a outra.',
        ],
        [
            'nome' => 'Fundo das atividades',
            'como' => 'Vale para todos os certificados de atividade, menos nas atividades que escolherem um fundo próprio no cadastro delas.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin', 'publicar_trava'],
];
