<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Comprovações de divulgação',
    'resumo' => 'A lista de tudo o que os participantes enviaram, com filtros, seleção e operações em lote. É aqui que a organização confere por amostragem e corrige o que não corresponde à regra.',
    'operacoes' => [
        [
            'nome' => 'Filtros',
            'como' => 'Busca por nome ou correio eletrônico, rede social, ação, situação e período de envio, todos combináveis. Clique no funil para aplicar e na seta para limpar. As opções de rede e de ação mostram só o que este evento recebeu.',
            'observacao' => 'O número de comprovações encontradas, a exportação e a paginação acompanham o filtro escolhido.',
        ],
        [
            'nome' => 'Exportar',
            'icone' => 'baixar',
            'como' => 'Baixa a lista filtrada atual em planilha de texto separada por ponto e vírgula, com participante, rede, ação, pontos, horário, situação e motivo.',
        ],
        [
            'nome' => 'Conferir uma comprovação',
            'como' => 'A coluna do participante mostra, abaixo do nome, a conta que ele cadastrou em Meu Perfil naquela rede (no WhatsApp, o telefone e se está marcado como WhatsApp). Em "Prova", o primeiro ícone abre a publicação em outra aba e o segundo abre a imagem da tela. O selo roxo avisa quando a mesma imagem apareceu em mais de um participante.',
            'observacao' => 'A imagem só abre para o Administrador: ela pode conter dados de quem não escolheu estar ali.',
        ],
        [
            'nome' => 'Marcar linhas',
            'como' => 'A caixa do cabeçalho marca e desmarca todas as linhas da página. As operações em lote agem só sobre o que estiver marcado, e o envio sem nada marcado é recusado com aviso.',
        ],
        [
            'nome' => 'Anular',
            'como' => 'Exige um motivo, que cada pessoa atingida recebe no aviso do sino e por e-mail. Os pontos saem de toda soma na hora. Em lote, o mesmo motivo vale para todas as selecionadas, e cada pessoa recebe um e-mail só, pela fila de envio, mesmo que tenha mais de uma comprovação anulada.',
            'observacao' => 'O crédito anulado não volta sozinho: o único caminho de volta é desfazer a anulação.',
        ],
        [
            'nome' => 'Desfazer anulação',
            'como' => 'Devolve os pontos e avisa cada pessoa pelo sino e por e-mail. É o conserto de uma anulação feita por engano.',
        ],
        [
            'nome' => 'Apagar as imagens das selecionadas',
            'como' => 'Apaga do disco só a imagem das linhas marcadas. As comprovações, os pontos e a proteção contra reenvio continuam. Não pode ser desfeito.',
        ],
        [
            'nome' => 'Excluir',
            'como' => 'Tira a comprovação da lista e de toda soma de pontos. Serve para limpar lançamento de teste ou registro que não deveria existir.',
            'observacao' => 'A linha não é apagada do banco: ela continua na trilha de auditoria, e o resumo da imagem continua impedindo que a mesma captura de tela seja enviada outra vez. Para ver o que foi excluído, escolha "Excluídas" no filtro de situação; de lá sai o botão de restaurar.',
        ],
        [
            'nome' => 'Apagar todas as imagens de comprovação',
            'como' => 'Limpa de uma vez as imagens guardadas do evento inteiro. Exige que o evento já tenha passado da própria data final e que o nome do evento seja digitado. As comprovações, os pontos e os resumos continuam.',
            'observacao' => 'As imagens podem conter dados de outras pessoas: depois do evento, apague-as.',
        ],
        [
            'nome' => 'Páginas',
            'como' => 'A lista mostra 50 comprovações por página. O filtro escolhido viaja entre as páginas, e mudar o filtro volta para a primeira.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin'],
];
