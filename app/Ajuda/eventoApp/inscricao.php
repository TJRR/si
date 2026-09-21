<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Minha inscrição',
    'resumo' => 'Dados da sua inscrição neste evento: documento, respostas dos campos definidos pelo Administrador e seu crachá de credenciamento.',
    'operacoes' => [
        [
            'nome' => 'Selo de situação',
            'como' => 'Mostra se sua inscrição já está confirmada ou ainda aguardando homologação do Administrador.',
        ],
        [
            'nome' => 'Crachá de credenciamento',
            'como' => 'Código único (QR (Quick Response) + 6 caracteres em texto) gerado desde a sua inscrição, usado para o credenciamento presencial no evento. O código em texto é só um substituto para digitação manual caso a leitura do QR falhe. "Imprimir crachá" abre uma versão pronta para impressão, sem menus nem barras do aplicativo.',
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
