<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Atividade: dados gerais',
    'resumo' => 'Cadastro de uma atividade específica do evento (curso, palestra, seminário). Inscrição e certificado próprio são decisões independentes desta atividade: elas não seguem o que está configurado no evento como um todo.',
    'operacoes' => [
        [
            'nome' => 'Tipo',
            'como' => 'Etiqueta colorida da atividade nas seções Destaques e Programação da página pública. Os tipos são cadastrados em "Tipos de atividade", por evento.',
        ],
        [
            'nome' => 'Destacar na página pública',
            'como' => 'Marca a atividade para aparecer na seção Destaques, quando ela estiver no modo vinculado às Atividades.',
        ],
        [
            'nome' => 'Modalidade',
            'como' => 'Presencial: só o código impresso (QR) confirma presença. Online: só o código de presença online (5 caracteres) confirma. Híbrido: os dois caminhos coexistem, cada participante usa o que corresponde à sua forma real de participação.',
        ],
        [
            'nome' => 'Exige inscrição prévia',
            'como' => 'Quando marcado, quem está inscrito no evento vê esta atividade no aplicativo e pode se inscrever nela. Quando desmarcado, a atividade aparece só como informação, sem controle de quem vai participar.',
        ],
        [
            'nome' => 'Emite certificado por esta atividade',
            'como' => 'Marcada, quem confirmou presença nesta atividade e quem a conduziu passam a ter um certificado próprio dela, com a duração dela (Início a Fim) como carga horária. O texto do documento é um só para todas as atividades do evento, escrito em Certificados, Configurações. Desmarcada, a atividade continua contando para o certificado de participação no evento, mas não gera documento próprio.',
        ],
        [
            'nome' => 'Plano de fundo do certificado desta atividade',
            'como' => 'O botão "Escolher imagem" abre a Biblioteca de mídia na pasta "Fundo Certificados", onde também é possível enviar uma arte nova na hora. O ícone ao lado deixa o fundo sem imagem e sem cor, e o seletor de cor pinta a folha de uma cor só. Sem escolha nenhuma aqui, vale a arte das atividades em Certificados, Configurações. A imagem precisa ter a proporção de uma folha A4 na horizontal.',
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
            'como' => 'Não afeta se a leitura é aceita: só classifica, na sub-aba "Presenças", se aquela confirmação específica conta como presença efetiva. Conta como efetiva a confirmação feita desde a abertura da leitura (o campo anterior) até o percentual escolhido aqui. O percentual é sempre calculado sobre a duração real da atividade (Início a Fim). A leitura é recusada depois do fim da atividade.',
        ],
        [
            'nome' => 'Pontos de presença e extra de pontualidade desta atividade',
            'como' => 'Em branco, a atividade usa os valores do tipo dela (Tipos de atividade). Preencha só quando esta atividade valer diferente do tipo; zero significa que ela não pontua. O extra de pontualidade vale para quem confirma presença até o número de minutos antes do início definido em Gamificação, Configurações. A presença pela internet pontua do mesmo jeito.',
            'observacao' => 'Mudar os valores não altera presença já pontuada. Para creditar presenças já registradas antes de a atividade ter pontos, use "Reconferir agora" em Gamificação, Classificação.',
        ],
        [
            'nome' => 'Código desta atividade / Imprimir código',
            'como' => 'O código fixo desta atividade, o mesmo para todos os participantes. "Imprimir código" abre um cartaz em tamanho A4 (QR (Quick Response) + código em texto) para afixar no espaço físico: é ele que o participante aponta a câmera para confirmar presença.',
        ],
        [
            'nome' => 'Código de presença online',
            'como' => 'Só aparece quando a modalidade não é presencial. Fixo, definido uma vez. Informe-o assim que a sala virtual abrir, antes do início: quem participa pela internet digita esse código no aplicativo para confirmar presença e pontua como quem está na sala, inclusive com o extra de pontualidade. O facilitador vê o código em "Minhas facilitações".',
        ],
    ],
    'conceitos' => [],
];
