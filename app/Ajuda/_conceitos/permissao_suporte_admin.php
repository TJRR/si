<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Diferença entre Suporte e Administrador',
    'texto' => 'Em quase todo o núcleo do concurso (Concursos, Trilhas, Etapas, Temas/Desafios, Designações, Apuração), o perfil Suporte vê exatamente os mesmos dados que o Administrador, mas em modo só leitura — os campos aparecem desabilitados e não há botão para gravar. Só o Administrador cria, edita ou remove nessas telas.',
];
