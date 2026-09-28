<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Estande',
    'resumo' => 'Cadastro de um estande do evento: dados que os participantes veem, pontos da visita e quem representa o estande.',
    'operacoes' => [
        [
            'nome' => 'Código de visita',
            'como' => 'Gerado pelo sistema na criação do estande e fixo, nunca muda. É o código do cartaz que o participante lê no aplicativo para registrar a visita.',
        ],
        [
            'nome' => 'Pontos por visita',
            'como' => 'Quantos pontos o participante ganha ao registrar a visita. Cada participante registra uma visita por estande em todo o evento.',
            'observacao' => 'O valor vale no momento da visita: mudar depois não altera os pontos de quem já visitou.',
        ],
        [
            'nome' => 'Descrição e logotipo',
            'como' => 'Aparecem no aplicativo do evento e na lista pública da página do evento. O texto alternativo descreve o logotipo para quem usa leitor de tela e é obrigatório quando há logotipo.',
        ],
        [
            'nome' => 'Ativo',
            'como' => 'Desmarcado, o estande some do aplicativo e da página e a leitura do código responde que ele não está recebendo visitas. As visitas já registradas continuam valendo.',
        ],
        [
            'nome' => 'Convidar representante',
            'como' => 'Informe nome e e-mail da pessoa do expositor ou do patrocinador. Sem conta no sistema, ela recebe o endereço para definir a senha; com conta, recebe o aviso e entra com o acesso de sempre. Ela passa a atualizar nome, descrição e logotipo, imprimir o cartaz e ver quantas visitas o estande recebeu.',
            'observacao' => 'Uma pessoa representa no máximo um estande por evento. Código, pontos, categoria e situação continuam só com o Administrador. Se a pessoa já tem um perfil do Concurso, de Suporte ou de Administrador, ao entrar ela vai primeiro para o painel desse perfil, e a tela avisa isso depois do convite; quem só é inscrito no evento entra direto no painel de representante.',
        ],
        [
            'nome' => 'Substituir representante',
            'como' => 'Troca a pessoa numa operação só: o acesso da anterior ao estande é encerrado e a nova recebe o convite.',
        ],
        [
            'nome' => 'Remover representante',
            'como' => 'Encerra o acesso da pessoa ao estande. Se ela não representar nenhum outro estande, perde também o perfil de representante.',
        ],
    ],
    'conceitos' => ['permissao_suporte_admin'],
];
