<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Documentos do Evento',
    'resumo' => 'Edital, anexos, retificações, resultados e atas deste evento, com versionamento automático. Funciona igual aos Documentos do Concurso, sem a escolha de trilha.',
    'operacoes' => [
        [
            'nome' => '+ Novo documento',
            'como' => 'Tipo, título e o arquivo: PDF ou documento editável (Word ou LibreOffice), por exemplo o modelo do resumo expandido.',
            'observacao' => 'Um novo envio com o mesmo tipo e título vira automaticamente uma nova versão do mesmo documento. Nunca sobrescreve o arquivo anterior.',
        ],
        [
            'nome' => 'Editar',
            'icone' => 'editar',
            'como' => 'Altera só o tipo e o título, não o arquivo.',
        ],
        [
            'nome' => 'Baixar',
            'icone' => 'baixar',
            'como' => 'Baixa a versão atual do arquivo.',
        ],
        [
            'nome' => 'Ver histórico de versões',
            'icone' => 'historico',
            'como' => 'Mostra as versões anteriores do mesmo documento.',
        ],
        [
            'nome' => 'Despublicar / Republicar',
            'icone' => 'despublicar',
            'como' => 'Despublicar tira o documento da página pública do evento sem apagar nada; Republicar coloca de volta.',
            'observacao' => 'O botão da página que aponta para um documento despublicado deixa de aparecer até ele ser republicado.',
        ],
        [
            'nome' => 'Remover todas as versões',
            'icone' => 'remover',
            'como' => 'Apaga o documento e todo o seu histórico.',
            'observacao' => 'Irreversível. O botão da página que apontava para ele fica sem destino e deixa de aparecer.',
        ],
        [
            'nome' => 'Uso na página do evento',
            'como' => 'Na seção de submissão (componente Cronograma, em Seções da página), o primeiro e o terceiro botão podem apontar para um documento daqui, por exemplo "Acessar o Edital completo" (primeiro) e "Baixar o modelo do resumo expandido" (terceiro). Ele abre sempre a versão atual: ao enviar uma retificação com o mesmo tipo e título, o botão passa a abrir a versão nova sozinho.',
        ],
        [
            'nome' => 'Reordenar',
            'como' => 'Ver conceito "Reordenar por arraste" abaixo.',
        ],
    ],
    'conceitos' => ['nunca_apaga_so_versiona', 'reordenar_arraste'],
];
