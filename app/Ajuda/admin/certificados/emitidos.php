<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Certificados emitidos',
    'resumo' => 'Os documentos já emitidos neste evento, com o que foi impresso em cada um, o código de conferência, a baixa e o cancelamento.',
    'operacoes' => [
        [
            'nome' => 'O que a lista mostra',
            'como' => 'Nome, condição e carga horária vêm da própria linha do certificado, e são exatamente o que foi impresso. Nada aqui é recalculado: é com esses dados que se confere o documento que a pessoa apresenta, mesmo que a presença dela tenha mudado depois.',
        ],
        [
            'nome' => 'Página pública de conferência',
            'como' => 'O endereço aparece no alto da tela e vem impresso em todo certificado. Quem recebe um documento digita o código ali e vê se ele foi emitido por este sistema. O código de cada linha é um atalho que já abre a conferência dele.',
        ],
        [
            'nome' => 'Abrir um certificado',
            'como' => 'O ícone de baixar entrega o arquivo guardado, o mesmo que a pessoa recebeu. Certificado cancelado não é entregue.',
        ],
        [
            'nome' => 'Baixar vários',
            'como' => 'A seleção com "Baixar num arquivo único" devolve um PDF só, com um certificado por folha, pronto para imprimir. São até 50 por arquivo, e certificado cancelado não entra.',
        ],
        [
            'nome' => 'Cancelar',
            'como' => 'Exige motivo. O documento deixa de ser entregue e a página pública passa a informar o cancelamento, com a data. O arquivo continua guardado de propósito: ele pode já ter sido juntado a um processo, e apagar tornaria irreproduzível o que foi entregue.',
        ],
        [
            'nome' => 'Quem emitiu',
            'como' => 'A coluna de situação diz quando o documento foi retirado pelo próprio interessado. Sem essa marca, a emissão foi feita pela organização.',
        ],
        [
            'nome' => 'Corrigir um certificado errado',
            'como' => 'Não há correção do documento: ele é guardado como foi emitido. O caminho é cancelar e emitir de novo, depois de acertar o que estava errado (a presença, a régua ou o texto).',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin', 'nunca_apaga_so_versiona'],
];
