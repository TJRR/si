<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Inscrição em evento',
    'resumo' => 'Ponto de entrada público de um Evento. Qualquer conta já aprovada no sistema (equipe, avaliador, colaborador, inscrito ou visitante sem perfil) pode se inscrever, sem recriar cadastro. Quem acessa pelo celular, ou já tem o aplicativo instalado (mesmo no computador), já vê esta tela com a aparência do aplicativo. No computador comum, continua com a aparência do portal institucional.',
    'operacoes' => [
        [
            'nome' => 'Cadastrar com o Google / Cadastrar com e-mail e senha',
            'como' => 'Para quem ainda não tem conta no sistema: aprovação automática, sem espera, perfil "Inscrito em evento".',
        ],
        [
            'nome' => 'Entrar com o Google / Entrar com e-mail e senha',
            'como' => 'Para quem já tem conta (de qualquer perfil, inclusive já inscrito antes). "Entrar com o Google" e "Cadastrar com o Google" levam ao mesmo lugar: o sistema reconhece sozinho se a conta já existe.',
        ],
        [
            'nome' => 'Confirmar inscrição',
            'como' => 'Grava o "Número do Documento de Identificação" (obrigatório, estrutural) e os demais campos definidos pelo Administrador para este evento. O "Tipo de Documento de Identificação" vem antes do número; quando for CPF, o número recebe máscara automática desde a primeira tecla e é validado (dígito verificador) antes de salvar. Ao concluir, você já cai direto no painel do aplicativo.',
        ],
    ],
    'conceitos' => [],
];
