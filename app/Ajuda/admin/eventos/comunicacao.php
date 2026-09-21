<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Evento: Comunicação',
    'resumo' => 'Envia um aviso por e-mail (e uma notificação dentro do aplicativo) para os inscritos que você escolher neste evento. Por causa do limite do provedor de e-mail institucional, o envio é feito aos poucos: no máximo 10 e-mails a cada minuto; por isso um aviso para muitos inscritos pode levar alguns minutos até todos receberem.',
    'operacoes' => [
        ['nome' => 'Destinatários', 'como' => 'Todos os inscritos vêm marcados por padrão. Desmarque quem não deve receber, ou use "Marcar/desmarcar todos".'],
        ['nome' => 'Enviar aviso', 'como' => 'Só Administrador pode disparar (Suporte pode compor e ver o histórico, mas não enviar). O envio de e-mail acontece aos poucos em segundo plano. A tela não fica travada esperando.'],
        ['nome' => 'Histórico', 'como' => 'Mostra cada aviso já enviado neste evento: quem enviou, quantos destinatários, quantos e-mails já saíram e quantos falharam. Enquanto o envio ainda está em andamento, a situação mostra "Em andamento". Recarregue a página para ver o progresso atualizado.'],
        ['nome' => 'Ícone de mensagem', 'como' => 'Clique para ver o conteúdo completo (assunto e corpo) de um aviso já enviado.'],
        ['nome' => 'Falharam', 'como' => 'Quando algum e-mail falha, aparece a lista de quem não recebeu. Não há reenvio automático: para tentar de novo, componha um novo aviso desmarcando quem já recebeu com sucesso.'],
    ],
    'conceitos' => [],
];
