<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Atividade: dados gerais',
    'resumo' => 'Cadastro de uma atividade específica do evento (curso, palestra, seminário). Inscrição e certificado são decisões independentes desta atividade. Elas não seguem o que está configurado no evento como um todo.',
    'operacoes' => [
        [
            'nome' => 'Modalidade',
            'como' => 'Presencial: só o código impresso (QR) confirma presença. Online: só o código de presença online (5 caracteres) confirma. Híbrido: os dois caminhos coexistem, cada participante usa o que corresponde à sua forma real de participação.',
        ],
        [
            'nome' => 'Exige inscrição prévia',
            'como' => 'Quando marcado, quem está inscrito no evento vê esta atividade no aplicativo e pode se inscrever nela. Quando desmarcado, a atividade aparece só como informação, sem controle de quem vai participar.',
        ],
        [
            'nome' => 'Emite certificado',
            'como' => 'Só grava a intenção: a emissão do certificado em si ainda não está disponível.',
        ],
        [
            'nome' => 'Vagas',
            'como' => 'Em branco, a inscrição é ilimitada. Preenchendo um número, a atividade lota quando esse número de confirmados for atingido.',
        ],
        [
            'nome' => 'Ao lotar, aceita lista de espera',
            'como' => 'Só tem efeito quando "Vagas" está preenchido. Marcado, quem tentar se inscrever depois de lotada entra na lista de espera, visível em "Inscritos". O Administrador confirma manualmente quando quiser. Desmarcado, a atividade simplesmente para de aceitar novas inscrições ao lotar.',
        ],
        [
            'nome' => 'A confirmação de presença fica disponível a partir de',
            'como' => 'Antes desse horário (contado de trás para frente a partir do Início), o aplicativo recusa a leitura do código com uma mensagem informando quando ela abre. Evita confirmar presença muito antes da atividade de fato acontecer.',
        ],
        [
            'nome' => 'Considerar presença efetiva se a confirmação de presença ocorrer até',
            'como' => 'Não afeta se a leitura é aceita: só classifica, na sub-aba "Presenças" e futuramente no certificado, se aquela confirmação específica conta como presença efetiva. O percentual é sempre calculado sobre a duração real da atividade (Início a Fim).',
        ],
        [
            'nome' => 'Código desta atividade / Imprimir código',
            'como' => 'O código fixo desta atividade, o mesmo para todos os participantes. "Imprimir código" abre um cartaz em tamanho A4 (QR (Quick Response) + código em texto) para afixar no espaço físico: é ele que o participante aponta a câmera para confirmar presença.',
        ],
        [
            'nome' => 'Código de presença online',
            'como' => 'Só aparece quando a modalidade não é presencial. Fixo, definido uma vez. Comunique-o verbalmente durante a atividade: quem está participando online digita esse código no aplicativo para confirmar presença.',
        ],
    ],
    'conceitos' => [],
];
