<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Lançar notas',
    'resumo' => 'Variante da tela de avaliação usada quando todos os critérios da etapa compartilham exatamente o mesmo conjunto de campos: a ficha da submissão aparece uma única vez (recolhível), e os critérios ficam em cartões lado a lado em vez de abas. A escolha entre esta tela e a de abas é automática — feita pelo controller, não por você.',
    'operacoes' => [
        [
            'nome' => 'Ficha da submissão',
            'como' => 'Aparece uma vez, no topo, e pode ser recolhida/expandida — o mesmo conteúdo vale para todos os critérios ao lado.',
        ],
        [
            'nome' => 'Nota (por cartão de critério)',
            'como' => 'Digite um valor dentro da escala mostrada em cada cartão (mín.–máx. daquele critério), com casas decimais (passo de 0,1).',
        ],
        [
            'nome' => 'Feedback',
            'como' => 'Aparece por critério ou por submissão inteira, conforme a etapa foi configurada — nunca as duas formas ao mesmo tempo.',
        ],
        [
            'nome' => 'Baixar anexos',
            'como' => 'Arquivos PDF enviados pela equipe abrem/baixam a partir do próprio nome do arquivo, dentro da ficha.',
        ],
        [
            'nome' => 'Salvar notas',
            'como' => 'Grava as notas e feedbacks preenchidos até agora — pode ser usado mais de uma vez enquanto a avaliação não travar.',
            'observacao' => 'A avaliação trava automaticamente assim que todos os critérios desta submissão estiverem notados — mesmo antes de o resultado da etapa ser publicado. Depois de travada, os campos ficam somente leitura. Se a etapa estiver configurada com avanço automático, completar a última nota pendente pode disparar a publicação do resultado sozinha.',
        ],
    ],
    'conceitos' => ['sigilo_anonimato'],
];
