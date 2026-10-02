<?php

/**
 * Exporta um arquivo SQL completo do banco (estrutura e dados), para
 * pedido de auditoria ou ordem judicial. O arquivo contem todos os dados
 * pessoais do sistema. Ver Implantar.md, secao 13.7.
 *
 * Ensaio por padrao: so' lista as tabelas e a contagem de linhas. Para
 * gerar o arquivo:
 *   php database/exportar_dump_completo.php --fase=NN --confirmar
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auditoria;
use App\Core\Database;

$confirmar = in_array('--confirmar', $argv, true);
$fase = null;

foreach ($argv as $arg) {
    if (strpos($arg, '--fase=') === 0) {
        $fase = preg_replace('/[^A-Za-z0-9]/', '', substr($arg, strlen('--fase=')));
    }
}

if ($confirmar && ($fase === null || $fase === '')) {
    echo "ERRO: informe a fase. Ex.: php database/exportar_dump_completo.php --fase=32 --confirmar\n";
    exit(1);
}

$pdo = Database::conexao();
$dbConfig = require __DIR__ . '/../config/database.php';

$tabelas = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

echo "Banco: {$dbConfig['name']}\n";
echo "Tabelas encontradas: " . count($tabelas) . "\n\n";

$totalLinhas = 0;

foreach ($tabelas as $tabela) {
    $qtd = (int) $pdo->query("SELECT COUNT(*) FROM `{$tabela}`")->fetchColumn();
    $totalLinhas += $qtd;
    echo "  - {$tabela}: {$qtd} linha(s)\n";
}

echo "\nTotal de linhas em todas as tabelas: {$totalLinhas}\n";

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nenhum arquivo foi gerado.\n";
    echo "Para gerar o dump de verdade, rode: php database/exportar_dump_completo.php --fase=XX --confirmar\n";
    exit(0);
}

// Um nivel acima da raiz do projeto, fora do que o servidor web publica.
// Ver Implantar.md, secao 13.7.
$pastaDestino = __DIR__ . '/../..';
$nomeArquivo = 'dump_completo_fase' . $fase . '.sql';
$caminhoArquivo = $pastaDestino . '/' . $nomeArquivo;
$handle = fopen($caminhoArquivo, 'w');

if ($handle === false) {
    echo "ERRO: não foi possível criar o arquivo em {$caminhoArquivo}.\n";
    exit(1);
}

fwrite($handle, "-- Dump completo gerado em " . date('Y-m-d H:i:s') . " - banco {$dbConfig['name']}\n");
fwrite($handle, "-- ATENCAO: contem dados pessoais - tratar como confidencial.\n\n");
fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

foreach ($tabelas as $tabela) {
    echo "Exportando '{$tabela}'...\n";

    $criacao = $pdo->query("SHOW CREATE TABLE `{$tabela}`")->fetch();
    fwrite($handle, "DROP TABLE IF EXISTS `{$tabela}`;\n");
    fwrite($handle, $criacao['Create Table'] . ";\n\n");

    $stmt = $pdo->query("SELECT * FROM `{$tabela}`");

    while (($linha = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
        $colunas = array_keys($linha);
        $valores = array_map(function ($valor) use ($pdo) {
            return $valor === null ? 'NULL' : $pdo->quote($valor);
        }, array_values($linha));

        fwrite(
            $handle,
            'INSERT INTO `' . $tabela . '` (`' . implode('`, `', $colunas) . '`) VALUES (' . implode(', ', $valores) . ");\n"
        );
    }

    fwrite($handle, "\n");
}

fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
fclose($handle);

chmod($caminhoArquivo, 0640);

Auditoria::registrar('exportar_dump_completo', 'sistema', null, null, [
    'arquivo' => $nomeArquivo,
    'total_tabelas' => count($tabelas),
    'total_linhas' => $totalLinhas,
]);

echo "\n[ok] dump gerado em: {$caminhoArquivo}\n";
echo "Lembrete: dado sensivel - entregue por canal seguro e apague a copia local depois.\n";
