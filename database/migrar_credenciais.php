<?php

/**
 * Fase 35 (Parte C): transfere as credenciais de integracao de
 * config/local.php para a tabela credenciais_sistema (migration 115),
 * cifrando o que e' segredo.
 *
 * Existe para o operador nao precisar redigitar na tela uma chave privada
 * PEM de mais de mil e setecentos caracteres - colar isso a mao e' onde o
 * erro acontece.
 *
 * NAO apaga nada de config/local.php. Depois de confirmar que a integracao
 * continua funcionando pelo banco, o proprio operador esvazia os blocos no
 * arquivo, seguindo o guia de atualizacao. Enquanto os blocos estiverem la',
 * eles seguem valendo como reserva (ver config/google.php,
 * config/google_calendar.php e config/smtp.php).
 *
 * Modo de simulacao por padrao: sem --confirmar so' mostra o que faria.
 *
 * Uso:
 *   php database/migrar_credenciais.php
 *   php database/migrar_credenciais.php --confirmar
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Cifra;
use App\Repositories\CredencialSistemaRepository;

$confirmar = in_array('--confirmar', $argv, true);

// Grupos a transferir: bloco no arquivo => grupo na tabela. Os campos
// sigilosos estao declarados em CredencialSistemaRepository, nao aqui - um
// lugar so' pra essa decisao.
$grupos = [
    'google' => 'google_oauth',
    'google_service_account' => 'google_service_account',
    'smtp' => 'smtp',
];

$local = require __DIR__ . '/../config/local.php';

echo "\n";
echo "Transferencia de credenciais para o banco (cifradas)\n";
echo str_repeat('=', 60) . "\n\n";

if (!Cifra::chaveMestraConfigurada()) {
    echo "ERRO: chave-mestra ausente.\n\n";
    echo "Preencha ['cifra']['chave_mestra'] em config/local.php antes de rodar.\n";
    echo "Gere uma com bastante entropia:\n\n";
    echo "    openssl rand -base64 32\n\n";
    echo "ATENCAO: depois de gravar credenciais com uma chave-mestra, trocar\n";
    echo "essa chave torna ILEGIVEL tudo que foi guardado.\n\n";
    exit(1);
}

$repositorio = new CredencialSistemaRepository();

if ($repositorio->possuiAlgumaCredencial()) {
    echo "AVISO: ja existem credenciais gravadas no banco. Este roteiro\n";
    echo "sobrescreve os campos preenchidos no arquivo e mantem os demais.\n\n";
}

$totalCampos = 0;

foreach ($grupos as $blocoArquivo => $grupoTabela) {
    echo "Grupo '{$grupoTabela}' (bloco '{$blocoArquivo}' do arquivo):\n";

    if (empty($local[$blocoArquivo]) || !is_array($local[$blocoArquivo])) {
        echo "  bloco ausente no arquivo - nada a transferir\n\n";
        continue;
    }

    $valores = [];

    foreach ($local[$blocoArquivo] as $chave => $valor) {
        $valor = trim((string) $valor);

        if ($valor === '') {
            echo "  {$chave}: vazio no arquivo - ignorado\n";
            continue;
        }

        // Nunca imprime o valor. Campo longo (chave privada, segredo) sai
        // como impressao digital; o resto sai inteiro porque nao e' segredo.
        $ehSegredo = in_array($chave, ['private_key', 'client_secret', 'pass'], true);
        $mostrar = $ehSegredo ? Cifra::impressaoDigital($valor) . ' (cifrado)' : $valor;

        echo "  {$chave}: {$mostrar}\n";
        $valores[$chave] = $valor;
        $totalCampos++;
    }

    if ($confirmar && !empty($valores)) {
        $repositorio->salvarGrupo($grupoTabela, $valores, null);
        echo "  -> gravado\n";
    }

    echo "\n";
}

echo str_repeat('-', 60) . "\n";
echo "Campos encontrados no arquivo: {$totalCampos}\n\n";

if (!$confirmar) {
    echo "MODO DE SIMULACAO - nada foi gravado.\n";
    echo "Rode de novo com --confirmar para aplicar.\n\n";
    exit(0);
}

echo "Concluido.\n\n";
echo "Proximos passos, nesta ordem:\n";
echo "  1. Abra a aba Configurações > Segurança e confira as impressoes digitais.\n";
echo "  2. Use \"Testar conexão com o Google\" para confirmar que a credencial funciona.\n";
echo "  3. So' entao esvazie os blocos 'google', 'google_service_account' e 'smtp'\n";
echo "     em config/local.php, mantendo 'db' e 'cifra' intactos.\n\n";
