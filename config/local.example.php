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
    // Chave-mestra da cifragem das credenciais guardadas no banco. Gere com
    // "openssl rand -base64 32". Trocar a chave torna ilegivel o que ja foi
    // guardado. Ver Implantar.md, secoes 5 e 13.6.
    'cifra' => [
        'chave_mestra' => '',
    ],
    'google' => [
        'client_id' => 'SEU_CLIENT_ID.apps.googleusercontent.com',
        'client_secret' => 'SEU_CLIENT_SECRET',
        'redirect_uri' => 'http://localhost:8090/index.php?r=auth/googleCallback',
    ],
    // Conta de servico do Google (Agenda e presenca no Meet). Cole private_key
    // entre aspas duplas, como aparece no arquivo .json baixado, com os \n no
    // meio do texto. Vazio: a integracao fica indisponivel, sem quebrar o
    // resto. Ver Implantar.md, secao 13.4.
    'google_service_account' => [
        'client_email' => '',
        'private_key' => '',
        'token_uri' => 'https://oauth2.googleapis.com/token',
    ],
    // Conta de envio de e-mail. Vazio: os avisos ficam registrados como
    // "falhou", sem quebrar o resto.
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'user' => '',
        'pass' => '',
        'from_email' => 'npi@tjrr.jus.br',
        'from_name' => 'Premio de Inovacao TJRR',
    ],
];
