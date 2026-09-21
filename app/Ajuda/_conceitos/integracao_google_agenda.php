<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Integração com Google Agenda',
    'texto' => "Funciona igual em Mentoria e Oficina. Ao criar um horário, o organizador (mentor, na Mentoria; quem está logado, na Oficina) pode marcar \"Integrar com Google Agenda\", disponível só para quem loga com e-mail institucional @tjrr.jus.br. Quando marcada, o sistema cria sozinho o evento e a sala do Google Meet na agenda do organizador (nunca é preciso colar um hiperlink manualmente), e a caixa de hiperlink manual fica travada, vazia.\n\n"
        . "Estado da integração (etiqueta na listagem): \"Integrado\" (verde, pronto) / \"Gerando sala...\" (laranja, aguarde e recarregue) / \"Falha na integração\" (vermelho; use \"Verificar/Tentar novamente\").\n\n"
        . "Ao uma equipe reservar (Mentoria) ou se inscrever (Oficina), os e-mails dos integrantes entram automaticamente como convidados do evento, sem ação extra de ninguém. A situação de resposta de cada convidado aparece na listagem: Confirmado (verde), Recusado (vermelho), Talvez (laranja) ou Aguardando resposta (laranja).\n\n"
        . "Quem não marcar a integração continua com o fluxo manual de sempre: hiperlink do Meet digitado à mão, sem nenhuma chamada ao Google.",
];
