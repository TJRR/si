<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Evento: Dados Gerais',
    'resumo' => 'Nome, descrição, período e situação do evento. A "Mensagem de confirmação da inscrição" é o texto (editor rico) enviado por e-mail a quem se inscreve; em branco, usa um texto padrão. O modo de credenciamento decide se a inscrição pública já nasce homologada ("automático") ou precisa de conferência manual do Administrador na sub-aba "Inscritos" ("assistido").',
    'operacoes' => [
        [
            'nome' => 'Oferecer a impressão de crachá ao participante',
            'como' => 'Desmarcado, o aplicativo deixa de oferecer o botão "Imprimir crachá". O código do participante continua na tela "Minha inscrição" e na tela "Conectar com participante", e é lido direto da tela do celular.',
            'observacao' => 'O credenciamento no local, com código fixo lido pelo próprio participante, fica em Gamificação, Credenciamento, e é diferente do modo de credenciamento desta tela, que trata da homologação da inscrição.',
        ],
        ['nome' => 'Salvar', 'como' => 'Grava os dados do evento.'],
    ],
    'conceitos' => [],
];
