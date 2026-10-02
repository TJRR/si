<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Meu evento',
    'resumo' => 'Painel do aplicativo do Evento: mostra o evento em que você está inscrito e a situação da sua inscrição. O seu código de participante fica em "Minha inscrição" (menu ☰) e na tela "Conectar com participante".',
    'operacoes' => [
        [
            'nome' => 'Selo de situação',
            'como' => 'Mostra se sua inscrição já está confirmada ou ainda aguardando homologação do Administrador.',
        ],
        [
            'nome' => 'Seus pontos na gincana',
            'como' => 'Aparece quando o evento mostra a pontuação no aplicativo. É o seu total, somando todas as formas de pontuar. "Ver minha pontuação e a classificação" abre a sua posição, o extrato e os primeiros da classificação; "Regras do jogo" explica quanto vale cada coisa.',
            'observacao' => 'Depois do encerramento da gincana, o cartão avisa que a classificação está congelada: nada mais pontua.',
        ],
        [
            'nome' => '"Ler código"',
            'como' => 'Abre o leitor dos códigos fixos: o afixado no local de uma atividade (confirma a presença), o do credenciamento no local do evento (confirma a sua chegada) e o que o responsável por uma competição mostra na hora (registra a sua participação).',
        ],
        [
            'nome' => '"Conectar com participante"',
            'como' => 'Aparece quando o evento está com as conexões ativadas. Abre a leitura do código de outra pessoa (câmera, quando o navegador suportar, ou digitação do código de 6 caracteres) e mostra o seu próprio código. Uma leitura só conecta as duas e credita os pontos às duas; cada dupla conta uma vez. Logo abaixo do botão aparece o seu total, depois da primeira conexão.',
        ],
        [
            'nome' => '"Divulgação"',
            'como' => 'Aparece quando o evento está com a divulgação ativada. Abre a tela em que você comprova que publicou sobre o evento numa rede social, ou que passou a seguir um canal indicado pela organização, e recebe os pontos na hora. Logo abaixo do botão aparece o seu total, depois da primeira comprovação.',
        ],
        [
            'nome' => '"Estandes"',
            'como' => 'Aparece quando o evento tem estande de expositor ou de patrocinador recebendo visitas. Abre a lista dos estandes, os que você já visitou e o seu total de pontos em estandes; lá, "Registrar visita" lê o código do cartaz do estande. Logo abaixo do botão aparece o seu total de pontos, depois da primeira visita.',
        ],
        [
            'nome' => 'Bônus',
            'como' => 'Aparece quando o evento tem bônus automáticos ativos. Cada bônus mostra o nome dado pela organização, quanto falta para você fechá-lo (por exemplo, 3 de 5 atividades diferentes) e os pontos que ele vale; fechado, aparece com os pontos já creditados. Nada precisa ser enviado: a conta é feita a partir das suas confirmações de presença.',
        ],
        [
            'nome' => 'Bônus de ações',
            'como' => 'Alguns bônus pedem uma ação sua, e não presenças: ter inscrição no evento, preencher campos do perfil (a tela diz quais faltam e leva a Meu Perfil), confirmar o credenciamento no local (leva ao leitor) ou ser autor de um trabalho submetido. Cada um vale uma vez. O de perfil é conferido também quando você abre este painel, então vale mesmo que o perfil tenha sido preenchido fora do aplicativo.',
        ],
        [
            'nome' => '"Responder à pesquisa"',
            'como' => 'Aparece enquanto a pesquisa de satisfação estiver aberta e você ainda não tiver respondido. As respostas são guardadas separadas do seu nome, e responder vale pontos quando a organização cadastrou um bônus para isso.',
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
            'como' => 'Abre "Painel", "Minha inscrição" (dados que você preencheu e o seu código), "Meu Perfil" (seus dados, a aparência e a troca de senha, além do que você compartilha nas conexões), "Minha pontuação" e "Regras do jogo" (quando o evento mostra a pontuação) e "Sair".',
        ],
    ],
    'conceitos' => [],
];
