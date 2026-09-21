<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Oficina: novo horário / edição',
    'resumo' => 'Cadastro de um horário de oficina. A mesma tela atende a criação de um horário novo e a edição de um horário já criado; o que muda entre as duas está descrito em "Editar / Remover". O organizador é sempre quem está logado; não há seleção de mentor.',
    'operacoes' => [
        [
            'nome' => 'Tema',
            'como' => 'Obrigatório. É o texto exibido para as equipes escolherem se inscrever.',
        ],
        [
            'nome' => 'Datas',
            'como' => 'Início e fim do horário.',
        ],
        [
            'nome' => 'Restringir a quem está habilitado à etapa',
            'como' => 'Opcional. "Aberto a todos" (padrão) mantém o compromisso visível para todas as equipes do concurso. Escolhendo uma etapa, só enxerga e se inscreve a equipe habilitada a ela, pelo mesmo critério que libera a submissão: estar classificada na etapa anterior. Como etapa pertence a uma trilha, escolher uma etapa restringe o compromisso àquela trilha. Enquanto o resultado da etapa anterior não for publicado, ninguém vê o compromisso. Etapas que não restringem ninguém (a primeira da trilha, ou aquelas cuja anterior não é avaliada por avaliadores) aparecem na lista marcadas como "(não restringe)".',
        ],
        [
            'nome' => 'Integrar com Google Agenda',
            'como' => 'Ver conceito abaixo.',
        ],
        [
            'nome' => 'Hiperlink do Google Meet',
            'como' => 'Só usado quando a integração está desmarcada.',
        ],
        [
            'nome' => 'Editar / Remover',
            'como' => 'Só antes da data de início. A partir do horário marcado, o compromisso não pode mais ser alterado nem removido. Na edição, o organizador e a integração com o Google Agenda não mudam. Para trocar qualquer um dos dois, remova e crie outro. As equipes inscritas são avisadas por notificação e e-mail apenas quando o início, o fim ou o tema mudam de fato; alterar só o vínculo de etapa, o hiperlink ou a observação não dispara aviso nenhum.',
        ],
    ],
    'conceitos' => ['integracao_google_agenda'],
];
