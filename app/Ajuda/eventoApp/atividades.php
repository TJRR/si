<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Atividades',
    'resumo' => 'Cursos, palestras e seminários deste evento. Atividades que não exigem inscrição aparecem só como informação; as demais mostram sua situação de inscrição.',
    'operacoes' => [
        ['nome' => 'Confirmar presença', 'como' => 'Abre o leitor de código: aponte a câmera para o cartaz afixado no espaço físico da atividade, ou digite o código manualmente. Um único leitor serve para qualquer atividade do evento.'],
        ['nome' => 'Inscrever-se', 'como' => 'Confirma sua vaga na atividade, quando ainda há lugar.'],
        ['nome' => 'Entrar na lista de espera', 'como' => 'Aparece quando a atividade está lotada mas aceita lista de espera. O Administrador confirma sua vaga manualmente se abrir espaço, e você recebe um aviso.'],
        ['nome' => 'Cancelar', 'como' => 'Desfaz sua inscrição (confirmada ou na lista de espera) nesta atividade.'],
        ['nome' => 'Lotada', 'como' => 'A atividade não aceita mais inscrições nem lista de espera.'],
    ],
    'conceitos' => [],
];
