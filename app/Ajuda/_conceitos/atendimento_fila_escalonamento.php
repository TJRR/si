<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Fila pessoal e escalonamento',
    'texto' => 'Dúvidas e Requerimentos funcionam do mesmo jeito por baixo: cada um chega a um atendente responsável (Administrador, Suporte ou Colaborador) e fica na "fila pessoal" dele — a tela "Minhas escaladas" é onde ele vê o que está sob sua responsabilidade agora. De lá, o atendente pode Responder (resolve, some da fila de quem respondeu) ou Escalar (passa a responsabilidade para outro atendente específico, com histórico de quem escalou para quem). Um prazo de referência de 48h (SLA) marca cada item como "Em dia" ou "Atrasado" desde a última movimentação. O que muda entre os dois: em Requerimentos, decisões que liberam acesso a dados internos (aprovar, recusar, revogar) são exclusivas do Administrador — Suporte e Colaborador só respondem pedidos de esclarecimento ou escalam.',
];
