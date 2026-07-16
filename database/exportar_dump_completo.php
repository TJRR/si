<?php

/**
 * Fase 17 (Melhoria 2): exporta um dump SQL completo (estrutura + dados) do
 * banco - mecanismo para atender pedido de auditoria ou ordem judicial que
 * exija entrega da base de dados.
 *
 * Usa PDO (ja disponivel no projeto, "pdo_mysql") em vez de mysqldump: o
 * servidor de producao nao tem o cliente "mysql"/"mysqldump" instalado (ver
 * memoria de deploy da Fase 13) - so' PHP com extensao de banco, mesmo padrao
 * ja usado pra restaurar o dump inicial em producao.
 *
 * ATENCAO - dado sensivel: o arquivo gerado contem TODOS os dados pessoais
 * do sistema (CPF, nome, e-mail, telefone de participantes/avaliadores/
 * usuarios). Trate a saida como confidencial: transfira por canal seguro,
 * apague a copia local assim que entregue a quem de direito.
 *
 * Por padrao roda em modo consulta (dry-run): so lista as tabelas e a
 * contagem de linhas, sem gravar nada. Para gerar o arquivo de verdade:
 *   php exportar_dump_completo.php --confirmar
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
    echo "Para gerar o dump de verdade, rode: php exportar_dump_completo.php --confirmar\n";
    exit(0);
}

$pastaDestino = __DIR__ . '/../storage/exports';

if (!is_dir($pastaDestino)) {
    mkdir($pastaDestino, 0770, true);
}

$nomeArquivo = 'dump_completo_' . date('Ymd_His') . '.sql';
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
