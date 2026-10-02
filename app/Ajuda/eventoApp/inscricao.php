<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Fase 58: função em vez de array fixo (AjudaService aceita os dois), porque
 * o botão "Imprimir crachá" só existe quando o evento oferece crachá.
 */
return function (array $dados) {
    $ofereceCracha = !isset($dados['evento']['oferece_cracha']) || (int) $dados['evento']['oferece_cracha'] === 1;

    return [
        'titulo' => 'Minha inscrição',
        'resumo' => 'Dados da sua inscrição neste evento: documento, respostas dos campos definidos pelo Administrador e o seu código de participante.',
        'operacoes' => [
            [
                'nome' => 'Selo de situação',
                'como' => 'Mostra se sua inscrição já está confirmada ou ainda aguardando homologação do Administrador.',
            ],
            [
                'nome' => 'Código de participante',
                'como' => 'Código único (QR (Quick Response) + 6 caracteres em texto) gerado desde a sua inscrição. É o que outra pessoa lê para se conectar com você. O código em texto é só um substituto para digitação manual caso a leitura do QR falhe.'
                    . ($ofereceCracha ? ' "Imprimir crachá" abre uma versão pronta para impressão, sem menus nem barras do aplicativo.' : ' Neste evento não há crachá impresso: o código na tela do celular faz esse papel.'),
            ],
            [
                'nome' => 'Ícone de lupa no canto do QR',
                'como' => 'Amplia o QR em tela cheia com fundo branco, para facilitar a leitura em ambientes com pouca luz. Toque em qualquer lugar da tela para fechar.',
            ],
            [
                'nome' => 'Sino de notificações',
                'como' => 'Mostra avisos como a confirmação da sua inscrição. Um número vermelho indica quantas ainda não foram vistas; toque numa notificação para abri-la (marca como vista) ou no ícone de confirmação no topo para marcar todas de uma vez.',
            ],
            [
                'nome' => 'Menu (ícone ☰)',
                'como' => 'Abre "Painel" (volta à tela principal do aplicativo) e "Sair".',
            ],
        ],
        'conceitos' => [],
    ];
};
