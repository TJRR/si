<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Bônus do evento',
    'resumo' => 'Cadastro dos bônus automáticos deste evento: o nome que o participante vê, a forma de apurar, o que cada um exige e quanto vale.',
    'operacoes' => [
        [
            'nome' => 'Novo bônus',
            'como' => 'Escolha um nome livre ("Bingo da Inovação", "Maratona da Semana"), o tipo, o número que aquele tipo exige e os pontos. O sistema apura sozinho, a partir das presenças que já registra: não há nada a conferir à mão.',
        ],
        [
            'nome' => 'Tipos disponíveis',
            'como' => 'Atividades diferentes com presença (informe quantas); dias diferentes com presença (informe quantos); atividades de um tipo escolhido (informe o tipo e quantas); e responder à pesquisa de satisfação (sem número). O nome do bônus é livre, o tipo é a forma de apurar.',
            'observacao' => 'Uma forma de apurar que não esteja nesta lista precisa de programação nova, porque cada tipo é uma consulta ao banco de dados.',
        ],
        [
            'nome' => 'Dois bônus do mesmo tipo',
            'como' => 'São permitidos e somam: cadastrando "cinco atividades, 15 pontos" e "dez atividades, 30 pontos", quem chega a dez recebe os dois. O que não é aceito é repetir o mesmo tipo com o mesmo número, que seria o mesmo bônus duas vezes.',
        ],
        [
            'nome' => 'Editar um bônus que já concedeu pontos',
            'como' => 'O tipo fica travado, porque os créditos já dados passariam a valer por outra regra. O nome, a descrição, o número exigido e os pontos continuam editáveis: baixar o número credita na hora quem já cumpria, e subir não tira nada de quem já recebeu.',
        ],
        [
            'nome' => 'Remover',
            'como' => 'Bônus que nunca concedeu pontos é removido de verdade. Depois do primeiro crédito, "remover" passa a desativar: o bônus some do aplicativo e deixa de ser apurado, e quem já ganhou continua com os pontos.',
        ],
        [
            'nome' => 'Exportar quem ganhou',
            'como' => 'Gera a relação em planilha, com nome, documento e data, para quem precisa entregar um prêmio no balcão. Aparece nos bônus que já têm ao menos um crédito.',
        ],
        [
            'nome' => 'Ordem',
            'como' => 'A ordem desta lista é a ordem em que os bônus aparecem no painel do participante.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin', 'reordenar_arraste'],
];
