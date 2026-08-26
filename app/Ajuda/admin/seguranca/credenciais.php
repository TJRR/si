<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Segurança — credenciais',
    'resumo' => 'Credenciais que ligam o sistema ao Google (Agenda, Meet e login) e ao envio de e-mail. Ficam cifradas no banco, não em arquivo. Exclusiva do Administrador com perfil global.',
    'operacoes' => [
        [
            'nome' => 'Campos de segredo abrem vazios',
            'icone' => 'cadeado_fechado',
            'como' => 'A tela nunca devolve o valor de um segredo. Deixe o campo em branco para manter o que já está gravado; preencha só quando quiser substituir.',
        ],
        [
            'nome' => 'Impressão digital',
            'como' => 'No lugar do valor, a tela mostra algo como "sha256:a3f1c2d4e5b60718". É um resumo do segredo: a mesma credencial sempre gera o mesmo código, e não há como voltar dele para a credencial.',
            'observacao' => 'Serve para duas perguntas do dia a dia: "é a mesma chave de antes?" (compare com o código de semanas atrás) e "desenvolvimento e produção estão iguais?" (compare as duas telas). Não é preciso rodar comando nenhum.',
        ],
        [
            'nome' => 'Testar conexão com o Google',
            'como' => 'Consulta as agendas visíveis para o e-mail informado. Só leitura — não cria, não altera e não apaga nada. Confirma que a credencial funciona sem exibi-la.',
        ],
        [
            'nome' => 'Auditoria e aviso',
            'como' => 'Abrir esta tela é registrado na auditoria e avisa os demais administradores globais, por notificação e e-mail. Gravar qualquer credencial também avisa.',
            'observacao' => 'Nem a auditoria nem o aviso contêm o valor da credencial: registram apenas quem, o quê e quando. Na mesma sessão, o aviso não se repete antes de 30 minutos.',
        ],
        [
            'nome' => 'Chave-mestra',
            'como' => 'É o que cifra e decifra tudo desta tela, e mora em config/local.php, no servidor. Sem ela, a tela avisa e o sistema continua usando as credenciais do próprio arquivo, sem quebrar nada.',
            'observacao' => 'Se a chave-mestra for trocada, o que já estava guardado deixa de ser legível — a tela mostra "Não foi possível ler este valor" e basta recadastrar os segredos aqui.',
        ],
    ],
    'conceitos' => [],
];
