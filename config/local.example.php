<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'db' => [
        'host' => 'db',
        'port' => '3306',
        'name' => 'npi_si_dev',
        'user' => 'npi_si',
        'pass' => 'si_dev_pass',
    ],
    'app' => [
        'base_path' => '',
        'env' => 'local',
    ],
    'google' => [
        'client_id' => 'SEU_CLIENT_ID.apps.googleusercontent.com',
        'client_secret' => 'SEU_CLIENT_SECRET',
        'redirect_uri' => 'http://localhost:8090/index.php?r=auth/googleCallback',
    ],
    // Fase 31: Service Account com Domain-Wide Delegation, usada para
    // integrar Mentoria/Oficina com o Google Agenda do organizador (nunca
    // OAuth individual por admin - ver DeployFase31.md para o setup
    // completo no Google Cloud/Workspace). Cole private_key entre ASPAS
    // DUPLAS, exatamente como aparece no campo "private_key" do .json
    // baixado (com os \n no meio do texto tal como estao) - aspas duplas
    // fazem o PHP converter cada \n numa quebra de linha real sozinho, sem
    // precisar reformatar nada na mao. Vazio = integracao com Google
    // Agenda fica indisponivel, sem quebrar o resto da aplicacao (fail-soft).
    'google_service_account' => [
        'client_email' => '',
        'private_key' => '',
        'token_uri' => 'https://oauth2.googleapis.com/token',
    ],
    // Em producao: preencher 'user'/'pass' com a conta e senha de app do Google Workspace
    // ja validadas no spike tecnico (smtp.gmail.com:587). Vazio = notificacoes ficam
    // registradas como 'falhou' sem quebrar o restante da aplicacao.
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'user' => '',
        'pass' => '',
        'from_email' => 'npi@tjrr.jus.br',
        'from_name' => 'Premio de Inovacao TJRR',
    ],
];
