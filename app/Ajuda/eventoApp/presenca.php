<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Ler código',
    'resumo' => 'Um leitor só para os códigos fixos do evento: o afixado no espaço de uma atividade (confirma a sua presença), o do credenciamento no local (confirma a sua chegada ao evento) e o que o responsável por uma competição mostra na hora (registra a sua participação). O próprio código diz o que foi lido.',
    'operacoes' => [
        [
            'nome' => '"Usar a câmera"',
            'como' => 'Só aparece em navegadores com suporte à leitura automática de QR (Quick Response) (Chrome/Edge). Pede permissão de câmera e, ao apontar para o cartaz ou para a tela do responsável, confirma sozinha. Em Safari e Firefox esse botão não aparece; a confirmação é sempre pelo campo abaixo.',
        ],
        [
            'nome' => 'Campo de digitação manual',
            'como' => 'Digite o código de 6 caracteres mostrado no cartaz e toque em "Validar". Quem participa de uma atividade pela internet digita aqui o código de 5 caracteres que o facilitador informa ao abrir a sala virtual. Funciona em qualquer navegador, inclusive como alternativa se a leitura da câmera falhar.',
        ],
        [
            'nome' => 'Presença em atividade',
            'como' => 'A leitura abre antes do início da atividade (15, 30 ou 60 minutos, conforme a atividade) e fecha no fim dela. Quando a atividade vale pontos, a mensagem mostra os pontos e, para quem confirmou com antecedência, o extra de pontualidade. Se a atividade exigir inscrição prévia e você não estiver inscrito(a) e confirmado(a) nela, a mensagem explica o motivo.',
            'observacao' => 'Quem conduz a atividade tem a presença registrada, sem pontos.',
        ],
        [
            'nome' => 'Credenciamento no local',
            'como' => 'O código fica nas paredes do auditório e no balcão de entrada. Vale uma vez, só dentro do período definido pela organização, e só na presença física.',
        ],
        [
            'nome' => 'Participação em competição',
            'como' => 'Quem canta ou compete lê o código que o responsável mostra na hora da participação. Vale uma vez por competição, sem inscrição prévia, e só no horário da atividade ligada a ela.',
        ],
        [
            'nome' => 'Resultado',
            'como' => 'Ler o mesmo código de novo não é erro: só confirma que a leitura já estava registrada. Muitas tentativas com código inexistente em seguida bloqueiam novas tentativas por alguns minutos; um código válido continua funcionando.',
            'observacao' => 'Depois do encerramento da gincana, presença e credenciamento continuam sendo registrados, sem pontos, e a participação em competição é recusada.',
        ],
    ],
    'conceitos' => [],
];
