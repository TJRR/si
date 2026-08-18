<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Sigilo e anonimato',
    'texto' => "Este sistema usa \"sigilo\" e \"anonimato\" em três situações diferentes — não são a mesma coisa:\n\n"
        . "1. Sigilo da avaliação (por etapa, configurado em Etapas — novo/editar): quando é \"Cega\", o avaliador nunca vê a equipe nem os participantes da submissão que está avaliando — só um número estável (\"Equipe {número}\"), atribuído uma única vez por etapa e nunca recalculado a partir da ordem de envio, para não indicar quem enviou primeiro.\n\n"
        . "2. Anonimato do avaliador para o participante: independentemente do modo de sigilo acima, o participante nunca vê o nome de quem avaliou — em \"Notas e Feedback\", cada avaliador aparece só como \"Avaliador 1\", \"Avaliador 2\"...\n\n"
        . "3. Anonimização por linha no relatório de auditoria (PDF): as iniciais usadas para identificar avaliadores nesse relatório são geradas localmente, por submissão — a mesma pessoa recebe letras diferentes em submissões diferentes, de propósito, para não permitir cruzar linhas e identificar o avaliador entre submissões. Já o \"Relatório de notas\" (uso interno) usa as iniciais reais do avaliador na etapa, sem esse cuidado — não deve circular fora do Administrador.",
];
