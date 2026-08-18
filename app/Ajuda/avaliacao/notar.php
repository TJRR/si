<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Lançar notas',
    'resumo' => 'Ficha da submissão organizada em abas por critério (ou em grade única, quando todos os critérios da etapa usam exatamente os mesmos campos), com o campo de nota e o feedback correspondentes a cada uma.',
    'operacoes' => [
        [
            'nome' => 'Abas de critério',
            'como' => 'Clique no nome do critério para trocar de aba. Cada aba mostra só o conteúdo da submissão relevante para aquele critério (conforme configurado em Critérios — novo/editar).',
        ],
        [
            'nome' => 'Nota',
            'como' => 'Digite um valor dentro da escala mostrada ao lado do campo (mín.–máx. daquele critério), com casas decimais (passo de 0,1).',
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
            'observacao' => 'A avaliação trava automaticamente assim que todos os critérios desta submissão estiverem notados — mesmo antes de o resultado da etapa ser publicado. Depois de travada, os campos ficam somente leitura e não é mais possível editar. Ela também trava se o resultado da etapa já tiver sido publicado. Se a etapa estiver configurada com avanço automático, completar a última nota pendente pode disparar a publicação do resultado sozinha.',
        ],
    ],
    'conceitos' => ['sigilo_anonimato'],
];
