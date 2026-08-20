<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Oficina — novo horário',
    'resumo' => 'Criação de um horário de oficina. O organizador é sempre quem está logado — não há seleção de mentor.',
    'operacoes' => [
        [
            'nome' => 'Tema',
            'como' => 'Obrigatório — é o texto exibido para as equipes escolherem se inscrever.',
        ],
        [
            'nome' => 'Datas',
            'como' => 'Início e fim do horário.',
        ],
        [
            'nome' => 'Restringir a quem está habilitado à etapa',
            'como' => 'Opcional. "Aberto a todos" (padrão) mantém o compromisso visível para todas as equipes do concurso. Escolhendo uma etapa, só enxerga e se inscreve a equipe habilitada a ela — o mesmo critério que libera a submissão: estar classificada na etapa anterior. Como etapa pertence a uma trilha, escolher uma etapa restringe o compromisso àquela trilha. Enquanto o resultado da etapa anterior não for publicado, ninguém vê o compromisso. Etapas que não restringem ninguém (a primeira da trilha, ou aquelas cuja anterior não é avaliada por avaliadores) aparecem na lista marcadas como "(não restringe)".',
        ],
        [
            'nome' => 'Integrar com Google Agenda',
            'como' => 'Ver conceito abaixo.',
        ],
        [
            'nome' => 'Link Meet manual',
            'como' => 'Só usado quando a integração está desmarcada.',
        ],
        [
            'nome' => 'Editar / Remover',
            'como' => 'Só antes da data de início. A partir do horário marcado, o compromisso não pode mais ser alterado nem removido. Na edição, o organizador e a integração com o Google Agenda não mudam — para trocar qualquer um dos dois, remova e crie outro. Quem já reservou ou se inscreveu é avisado por notificação e e-mail quando o horário muda.',
        ],
    ],
    'conceitos' => ['integracao_google_agenda'],
];
