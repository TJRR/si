<?php

/**
 * Fase 31: diagnostico de conectividade da integracao Google Agenda -
 * so' LEITURA (lista as agendas visiveis pro e-mail informado, nao cria
 * nem altera nada). Uso:
 *   php database/testar_google_calendar.php seu-email@tjrr.jus.br
 *
 * Rode isso depois de preencher config/local.php (bloco
 * google_service_account) e depois que a TI autorizar o Client ID no
 * Admin Console do Workspace (DeployFase31.md, secao 1) - e' o jeito mais
 * rapido de confirmar se a delegacao esta funcionando, sem precisar criar
 * um horario de verdade pela tela.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\GoogleServiceAccountAuth;
use App\Services\GoogleCalendarService;
use App\Services\GoogleCalendarSyncService;

function linha($ch = '-')
{
    echo str_repeat($ch, 78) . "\n";
}

$email = isset($argv[1]) ? trim($argv[1]) : '';

if ($email === '') {
    echo "Uso: php database/testar_google_calendar.php seu-email@tjrr.jus.br\n";
    echo "Informe o e-mail de um administrador/suporte real do sistema, com conta @tjrr.jus.br.\n";
    exit(1);
}

linha('=');
echo "Fase 31 - diagnostico de conectividade com o Google Agenda\n";
echo "E-mail a impersonar: {$email}\n";
linha('=');

// Passo 1: a chave da Service Account esta preenchida em config/local.php?
$config = require __DIR__ . '/../config/google_calendar.php';

if (empty($config['client_email']) || empty($config['private_key'])) {
    echo "[FALHOU] config/local.php ainda nao tem a chave da Service Account preenchida\n";
    echo "         (bloco 'google_service_account' - ver DeployFase31.md, secao 1, passo 6).\n";
    exit(1);
}

echo "[OK] Chave da Service Account encontrada em config/local.php ({$config['client_email']}).\n\n";

// Passo 2: a chave assina corretamente e o Google aceita trocar por um
// access token para ESTE e-mail (e' aqui que a Delegacao em Todo o Dominio
// entra em jogo - se a TI ainda nao autorizou o Client ID no Admin Console,
// ou autorizou com o escopo errado, a falha acontece exatamente aqui).
echo "Tentando obter um access token impersonando {$email}...\n";
$token = GoogleServiceAccountAuth::obterAccessToken($email, GoogleCalendarSyncService::ESCOPOS);

if ($token === null) {
    linha();
    echo "[FALHOU] Nao foi possivel obter um access token.\n\n";
    echo "Causas mais provaveis, em ordem de chance:\n";
    echo "  1. A TI ainda nao autorizou o Client ID da Service Account no Admin Console\n";
    echo "     do Workspace (DeployFase31.md, secao 1, passo 5) - ESSA e' a causa mais comum\n";
    echo "     quando a chave (passo anterior) ja esta OK. Pode levar alguns minutos pra\n";
    echo "     propagar depois que a TI autorizar - tente de novo em 5-10 minutos.\n";
    echo "  2. O Client ID ou os escopos autorizados no Admin Console nao batem exatamente\n";
    echo "     com os esperados. Escopos esperados (devem estar autorizados juntos, separados\n";
    echo "     por virgula, no MESMO registro do Client ID):\n";
    echo "       " . implode(",\n       ", GoogleCalendarSyncService::ESCOPOS) . "\n";
    echo "  3. O e-mail informado ({$email}) nao existe de verdade no Workspace tjrr.jus.br,\n";
    echo "     ou nao e' uma conta @tjrr.jus.br (confira se digitou certo).\n";
    echo "  4. A chave privada em config/local.php ficou corrompida ao colar (quebras de\n";
    echo "     linha perdidas) - confira o formato exato na secao 1, passo 6 do DeployFase31.md.\n";
    exit(1);
}

echo "[OK] Access token obtido com sucesso - a Delegacao em Todo o Dominio esta funcionando.\n\n";

// Passo 3: chamada real e inofensiva a Calendar API (so' lista agendas,
// nao cria nada) - confirma que a Calendar API esta habilitada no projeto
// GCP e que o escopo realmente da acesso de leitura.
echo "Listando as agendas visiveis para {$email}...\n";
$calendarios = GoogleCalendarService::listarCalendarios($token);

if ($calendarios === null) {
    linha();
    echo "[FALHOU] O token foi obtido, mas a chamada a Calendar API falhou.\n";
    echo "         Confira se a 'Google Calendar API' esta ativada no projeto GCP (DeployFase31.md,\n";
    echo "         secao 1, passo 1).\n";
    exit(1);
}

linha();
echo "[OK] Tudo funcionando! Agendas encontradas (" . count($calendarios) . "):\n";

foreach ($calendarios as $calendario) {
    echo "  - {$calendario['summary']} ({$calendario['id']})\n";
}

linha('=');
echo "Diagnostico concluido com sucesso. A integracao esta pronta para uso real.\n";
