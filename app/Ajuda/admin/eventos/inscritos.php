<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Evento: Inscritos',
    'resumo' => 'Lista geral de quem se inscreveu neste evento. As colunas variam conforme o "Formulário de inscrição" configurado. A coluna "Credenciamento" só aparece quando o modo de credenciamento (definido em Dados Gerais) é "Assistido"; nesse caso, cada inscrição fica pendente até o Administrador clicar em "Homologar".',
    'operacoes' => [
        ['nome' => 'Editar resposta', 'como' => 'Administrador e Suporte podem corrigir ou preencher a resposta de qualquer campo direto nesta tela, sem depender do participante reenviar o formulário.'],
        ['nome' => 'Coluna "Documento"', 'como' => 'Exibida formatada (###.###.###-##) quando o "Tipo de documento" da linha for CPF; para RG/RNE/Passaporte, aparece como foi digitado, sem máscara.'],
        ['nome' => 'Homologar', 'como' => 'Marca a inscrição como credenciada. Só aparece no modo "Assistido".'],
        ['nome' => 'Exportar (.csv)', 'como' => 'Gera a lista completa de inscritos, com as colunas exigidas pela EJURR (Educa Enfam), para importação no sistema deles. Abre no Excel ou LibreOffice.'],
    ],
    'conceitos' => [],
];
