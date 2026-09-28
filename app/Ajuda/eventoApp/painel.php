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
            'nome' => '"Estandes"',
            'como' => 'Aparece quando o evento tem estande de expositor ou de patrocinador recebendo visitas. Abre a lista dos estandes, os que você já visitou e o seu total de pontos em estandes; lá, "Registrar visita" lê o código do cartaz do estande. Logo abaixo do botão aparece o seu total de pontos, depois da primeira visita.',
        ],
        [
            'nome' => '"Anais"',
            'como' => 'Aparece só depois que a organização publica os Anais do evento, o volume em PDF com os trabalhos apresentados. Abre o arquivo em outra aba, com o título e, quando houver, o ISSN ou o ISBN logo abaixo do botão.',
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
