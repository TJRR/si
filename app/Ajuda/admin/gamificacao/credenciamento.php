<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Credenciamento no local',
    'resumo' => 'Código fixo do evento, impresso nas paredes do auditório e no balcão de entrada, que o próprio participante lê no aplicativo ao chegar. Diferente do "Modo de credenciamento" de Dados Gerais, que trata da homologação da inscrição.',
    'operacoes' => [
        [
            'nome' => 'Filtros',
            'como' => 'Busca por nome ou correio eletrônico e período do credenciamento. Clique no funil para aplicar e na seta para limpar.',
            'observacao' => 'O número de linhas encontradas e a exportação acompanham o filtro escolhido.',
        ],
        [
            'nome' => 'Marcar linhas',
            'como' => 'A caixa do cabeçalho marca e desmarca todas as linhas. As operações em lote agem só sobre o que estiver marcado, e o envio sem nada marcado é recusado com aviso.',
        ],
        [
            'nome' => 'Remover o credenciamento das selecionadas',
            'icone' => 'remover',
            'como' => 'Desfaz uma leitura feita por engano. A pessoa perde o bônus de credenciamento, que é reconferido na hora, e pode se credenciar de novo dentro da janela.',
        ],
        [
            'nome' => 'Exportar',
            'icone' => 'baixar',
            'como' => 'Baixa a lista filtrada em planilha de texto separada por ponto e vírgula, com nome, correio eletrônico e horário.',
        ],
        [
            'nome' => 'Aceitar o credenciamento no local',
            'como' => 'Liga a leitura. Na primeira vez que é ligado, o sistema gera o código, que nunca muda depois, porque já pode estar impresso.',
        ],
        [
            'nome' => 'A partir de / Até',
            'como' => 'Período em que a leitura é aceita, com data e hora. Em branco nos dois campos, valem os dias do evento inteiros. Fora do período, a leitura é recusada com a mensagem do período.',
        ],
        [
            'nome' => 'Imprimir o cartaz',
            'como' => 'Cartaz A4 com o QR (Quick Response) e o código em texto, para as paredes do auditório e o balcão.',
        ],
        [
            'nome' => 'Os pontos',
            'como' => 'Não ficam aqui. Cadastre em Bônus um bônus do tipo "Confirmar o credenciamento no local", com os pontos que ele vale; ele credita quem já se credenciou e quem se credenciar depois.',
            'observacao' => 'Só vale na presença física: não há código para quem acompanha pela internet. Depois do encerramento da gincana, o credenciamento continua sendo registrado, sem pontos.',
        ],
        [
            'nome' => 'Quem se credenciou',
            'como' => 'Lista nominal, na ordem da chegada, com o horário de cada leitura.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin'],
];
