<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Evento: Dados Gerais',
    'resumo' => 'Nome, descrição, período e situação do evento. A "Mensagem de confirmação da inscrição" é o texto (editor rico) enviado por e-mail a quem se inscreve; em branco, usa um texto padrão. O modo de credenciamento decide se a inscrição pública já nasce homologada ("automático") ou precisa de conferência manual do Administrador na sub-aba "Inscritos" ("assistido").',
    'operacoes' => [
        ['nome' => 'Salvar', 'como' => 'Grava os dados do evento.'],
    ],
    'conceitos' => [],
];
