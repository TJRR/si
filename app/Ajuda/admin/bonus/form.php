<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Cadastro de bônus',
    'resumo' => 'Define um bônus automático do evento: nome, forma de apurar, exigência e pontos.',
    'operacoes' => [
        [
            'nome' => 'Nome e descrição',
            'como' => 'O nome é o que o participante vê no painel e na mensagem de quando o bônus fecha, então vale escrever como a organização divulga. A descrição, opcional, é a frase que explica o que fazer para ganhar.',
        ],
        [
            'nome' => 'Tipo',
            'como' => 'A forma de apurar. "Atividades diferentes com presença" conta as atividades em que a pessoa confirmou presença, cada uma uma vez. "Dias diferentes com presença" conta os dias em que houve ao menos uma presença. "Atividades de um tipo escolhido" faz a primeira contagem restrita a um tipo de atividade. "Responder à pesquisa de satisfação" credita quem enviou a pesquisa.',
            'observacao' => 'O dia que conta é o dia em que a atividade começa, e não o instante da leitura do código, porque a confirmação abre até uma hora antes.',
        ],
        [
            'nome' => 'Quantas o bônus exige',
            'como' => 'O bônus fecha quando a pessoa chega a este número. Zero não é aceito nos tipos que pedem número: ninguém ganharia, ou todos ganhariam sem ter feito nada.',
        ],
        [
            'nome' => 'Tipo de atividade que conta',
            'como' => 'Só aparece no tipo que restringe por tipo de atividade. Atividade cadastrada sem tipo nunca entra nessa contagem, e a tela avisa quantas estão nessa situação.',
        ],
        [
            'nome' => 'Pontos',
            'como' => 'Quanto a pessoa ganha quando o bônus fecha. O valor é congelado no momento da concessão: mudar aqui vale só para quem fechar daqui em diante, e quem já recebeu mantém o que ganhou.',
        ],
        [
            'nome' => 'Bônus ativo',
            'como' => 'Desativado, o bônus some do aplicativo e deixa de ser apurado, sem apagar nenhum crédito já concedido.',
        ],
        [
            'nome' => 'Toda presença conta',
            'como' => 'Os bônus de presença não distinguem quem chegou no horário de quem chegou atrasado: qualquer confirmação registrada conta. Para não premiar uma presença, o caminho é não cadastrá-la como atividade, ou usar o bônus por tipo de atividade.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin'],
];
