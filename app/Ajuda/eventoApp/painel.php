<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Meu evento',
    'resumo' => 'Painel do aplicativo do Evento: mostra o evento em que você está inscrito e a situação da sua inscrição. Seu crachá de credenciamento fica em "Minha inscrição" (menu ☰). "Ler código" abre a leitura de código de outra pessoa.',
    'operacoes' => [
        [
            'nome' => 'Selo de situação',
            'como' => 'Mostra se sua inscrição já está confirmada ou ainda aguardando homologação do Administrador.',
        ],
        [
            'nome' => '"Ler código"',
            'como' => 'Abre a tela de leitura do código de credenciamento de outra pessoa (câmera, quando o navegador suportar, ou digitação manual do código de 6 caracteres).',
        ],
        [
            'nome' => '"Toque para instalar o aplicativo"',
            'como' => 'Aparece só quando o navegador já libera a instalação; some sozinho depois de instalado. Instalar é sempre opcional: usar direto pelo hiperlink também funciona.',
        ],
        [
            'nome' => 'Sino de notificações',
            'como' => 'Mostra avisos como a confirmação da sua inscrição. Um número vermelho indica quantas ainda não foram vistas; toque numa notificação para abri-la (marca como vista) ou no ícone de confirmação no topo para marcar todas de uma vez.',
        ],
        [
            'nome' => 'Menu (ícone ☰)',
            'como' => 'Abre "Painel", "Minha inscrição" (dados que você preencheu) e "Sair".',
        ],
    ],
    'conceitos' => [],
];
