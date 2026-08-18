<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Nunca apaga, só versiona ou despublica',
    'texto' => 'Em vários pontos do sistema, uma edição "por cima" de algo que já pode estar em uso não sobrescreve o que existe — cria uma versão nova (Documentos, Formulários) ou apenas some da vista pública sem apagar o dado (Blocos de conteúdo padrão, que só podem ser desativados, nunca removidos). O objetivo é nunca perder histórico nem quebrar algo que uma equipe já viu ou preencheu. Onde existir, use o histórico de versões para consultar o que havia antes.',
];
