<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Reabertura da Fase 51 (achado da equipe de Teste Cego, generalidade): a
 * ajuda deste formulario e' uma FUNCAO que recebe os dados da tela
 * (AjudaService::paraView), para descrever so' o que o formulario deste
 * evento realmente mostra - formas de envio habilitadas, extensoes e limite
 * de tamanho reais, avaliacao as cegas, declaracoes, limite de coautores e
 * inscricao automatica. Outro evento, com outra configuracao, ganha outra
 * ajuda sem mexer neste arquivo.
 */
return function (array $tela) {
    $config = isset($tela['config']) && is_array($tela['config']) ? $tela['config'] : [];
    $metodos = isset($tela['metodosHabilitados']) ? array_values($tela['metodosHabilitados']) : [];
    $extensoes = isset($tela['extensoesHabilitadas']) ? $tela['extensoesHabilitadas'] : [];
    $temTermos = !empty($tela['termos']);
    $temEixos = !empty($tela['eixos']);
    $temNaturezas = !empty($tela['naturezas']);
    $sigiloCego = isset($config['sigilo_cego']) && (int) $config['sigilo_cego'] === 1;
    $exigeTelefone = isset($config['exige_telefone_contato']) && (int) $config['exige_telefone_contato'] === 1;
    $maxAutores = isset($config['quantidade_maxima_autores']) ? (int) $config['quantidade_maxima_autores'] : 1;
    $maxCoautores = max(0, $maxAutores - 1);
    $tamanhoMb = isset($config['tamanho_maximo_mb']) ? (int) $config['tamanho_maximo_mb'] : 0;
    $inscricaoAutomatica = !empty($config['inscrever_autores_ao_submeter']);

    $descricaoMetodo = [
        'formulario' => 'digitar o texto direto na tela',
        'link_externo' => 'informar o endereço eletrônico de um documento externo',
        'documento_editavel' => 'enviar um documento editável' . (!empty($extensoes) ? ' (' . implode(', ', $extensoes) . ')' : ''),
        'documento_nao_editavel' => 'enviar um documento em PDF',
    ];
    $formas = [];

    foreach ($metodos as $metodo) {
        if (isset($descricaoMetodo[$metodo])) {
            $formas[] = $descricaoMetodo[$metodo];
        }
    }

    $operacoes = [];

    $obrigatoriosDoTrabalho = ['título do trabalho'];

    if ($temEixos) {
        $obrigatoriosDoTrabalho[] = 'eixo temático';
    }

    if ($temNaturezas) {
        $obrigatoriosDoTrabalho[] = 'natureza';
    }

    if ($exigeTelefone) {
        $obrigatoriosDoTrabalho[] = 'telefone para contato, com DDD (8 ou 9 dígitos depois do DDD)';
    }

    $operacoes[] = [
        'nome' => 'Preencher antes de entrar',
        'como' => 'O formulário pode ser visto e preenchido antes de entrar no sistema. Ao terminar, clique em "Entrar para enviar" e faça a entrada ou crie a sua conta. Na volta, o que foi preenchido e os arquivos escolhidos reaparecem, com o aviso "Recuperamos o que você preencheu": confira e envie.',
        'observacao' => 'O preenchimento fica guardado só neste navegador e só nesta aba, por até 2 horas; nada é enviado antes de você entrar. O botão "Descartar rascunho" apaga o que foi guardado. Em janela de navegação privada, ou em navegador que não permite guardar, a tela avisa no topo: nesse caso, entre no sistema antes de preencher.',
    ];

    $operacoes[] = [
        'nome' => 'Campos obrigatórios',
        'como' => 'Os campos marcados com asterisco (*) são obrigatórios; os marcados com "(opcional)" podem ficar em branco. Nos dados do trabalho, são obrigatórios: ' . implode(', ', $obrigatoriosDoTrabalho) . '.',
        'observacao' => 'Se o envio for recusado, aparece a caixa "Seu trabalho NÃO foi enviado" com o motivo, o campo com problema fica em vermelho e a tela vai até ele. O que você preencheu é mantido, e os arquivos que você tinha escolhido são recolocados quando o navegador permite; se algum não voltar, escolha de novo.',
    ];

    if ($maxCoautores > 0) {
        $operacoes[] = [
            'nome' => 'Autor principal e coautores',
            'como' => 'Nome, CPF e e-mail são obrigatórios para cada autor; cargo e órgão de origem são opcionais. O CPF, o cargo e o órgão do autor principal, se já cadastrados em "Meu Perfil", vêm preenchidos. O e-mail do autor principal é sempre o da sua conta e não pode ser alterado. Este evento aceita até ' . $maxAutores . ' autores, incluindo o principal: o botão "Adicionar coautor" para em ' . $maxCoautores . ' coautor' . ($maxCoautores === 1 ? '' : 'es') . '. Coautor é opcional; um bloco de coautor pode ser removido a qualquer momento.',
        ];
    } else {
        $operacoes[] = [
            'nome' => 'Autor principal',
            'como' => 'Este evento aceita um único autor por trabalho. Nome, CPF e e-mail são obrigatórios; cargo e órgão de origem são opcionais. Se já estiverem cadastrados em "Meu Perfil", vêm preenchidos. O e-mail é sempre o da sua conta e não pode ser alterado.',
        ];
    }

    if (isset($config['permite_multiplos_trabalhos_por_pessoa']) && (int) $config['permite_multiplos_trabalhos_por_pessoa'] === 0) {
        $operacoes[] = [
            'nome' => 'Um trabalho por pessoa',
            'como' => 'Neste evento, cada pessoa participa de um único trabalho, como autor principal ou como coautor. O envio é recusado se a sua conta, o CPF ou o e-mail de qualquer autor já constar em outro trabalho do evento. Quem já consta num trabalho vê, no lugar do formulário, um aviso com o botão "Meus trabalhos".',
            'observacao' => 'Se o seu CPF foi informado errado num envio anterior, não envie de novo: corrija o CPF em "Meu perfil" (Dados complementares, campo Documento, com o tipo CPF). A correção passa também para os trabalhos que você já enviou.',
        ];
    }

    if (count($formas) > 1) {
        $operacoes[] = [
            'nome' => 'Forma de envio',
            'como' => 'Escolha como enviar o conteúdo do trabalho: ' . implode('; ', $formas) . '. A tela mostra só os campos da forma escolhida.',
        ];
    } elseif (count($formas) === 1) {
        $operacoes[] = [
            'nome' => 'Forma de envio',
            'como' => 'Neste evento o trabalho é enviado de uma única forma: ' . $formas[0] . '. Por isso a tela não pede para escolher.',
        ];
    }

    if (in_array('documento_editavel', $metodos, true) || in_array('documento_nao_editavel', $metodos, true)) {
        $operacoes[] = [
            'nome' => 'Arquivos',
            'como' => 'O tamanho máximo de cada arquivo é ' . $tamanhoMb . 'MB' . (in_array('documento_editavel', $metodos, true) && !empty($extensoes) ? ' e as extensões aceitas para o documento editável são ' . implode(', ', $extensoes) : '') . '. O sistema confere o conteúdo do arquivo, não só o nome: um arquivo de outro tipo, mesmo renomeado, é recusado.',
            'observacao' => 'Se o envio for recusado, confira os campos de arquivo: o sistema recoloca os arquivos que você tinha escolhido, quando o navegador permite. O arquivo que motivou a recusa não volta e precisa ser escolhido de novo.',
        ];
    }

    if ($sigiloCego) {
        $operacoes[] = [
            'nome' => 'Duas versões (avaliação às cegas)',
            'como' => 'Como a avaliação deste evento é às cegas, é preciso enviar duas versões com o mesmo conteúdo: uma sem qualquer identificação de autoria (a que o avaliador vê) e outra completa, com os dados de autoria (usada só se o trabalho for aprovado).',
            'observacao' => in_array('formulario', $metodos, true)
                ? 'Ao digitar o texto direto na tela, a ocultação de autoria é estrutural (os campos de nome e CPF ficam fora do texto), mas não impede a pessoa de se identificar dentro do próprio texto: esse cuidado é de quem escreve.'
                : null,
        ];
    }

    if ($temTermos) {
        $operacoes[] = [
            'nome' => 'Declarações',
            'como' => 'Caixas no fim do formulário, cadastradas pela organização do evento. As obrigatórias precisam ser marcadas para enviar, e o texto aceito fica guardado junto com a submissão, como estava no dia do envio.',
        ];
    }

    if ($inscricaoAutomatica) {
        $operacoes[] = [
            'nome' => 'Inscrição automática e protocolo',
            'como' => 'Ao enviar o trabalho' . ($maxCoautores > 0 ? ', você e os coautores indicados' : ', você') . ' são inscritos no evento automaticamente e recebem um e-mail com o número do protocolo. Quem ainda não tem conta no sistema ganha uma, com um endereço para definir a senha (vale por 7 dias). Depois, o trabalho pode ser acompanhado em "Meus trabalhos", no painel do evento.',
        ];
    } else {
        $operacoes[] = [
            'nome' => 'Protocolo',
            'como' => 'Depois do envio, a tela mostra o número do protocolo do trabalho. Guarde esse número. O acompanhamento fica em "Meus trabalhos", no painel do evento.',
        ];
    }

    foreach ($operacoes as $posicao => $operacao) {
        if (array_key_exists('observacao', $operacao) && $operacao['observacao'] === null) {
            unset($operacoes[$posicao]['observacao']);
        }
    }

    return [
        'titulo' => 'Submeter trabalho',
        'resumo' => 'Formulário de envio de um artigo ou resumo expandido para este evento.',
        'operacoes' => $operacoes,
        'conceitos' => $sigiloCego ? ['sigilo_anonimato'] : [],
    ];
};
