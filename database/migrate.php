<?php

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

$dbConfig = require __DIR__ . '/../config/database.php';

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $dbConfig['host'],
    $dbConfig['port'],
    $dbConfig['name']
);

$pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS migracoes_executadas (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        arquivo VARCHAR(190) NOT NULL,
        aplicada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_migracoes_arquivo (arquivo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$executadas = $pdo->query('SELECT arquivo FROM migracoes_executadas')->fetchAll(PDO::FETCH_COLUMN);

$arquivos = glob(__DIR__ . '/migrations/*.sql');
sort($arquivos);

foreach ($arquivos as $caminho) {
    $nome = basename($caminho);

    if (in_array($nome, $executadas, true)) {
        echo "Ja aplicada: {$nome}\n";
        continue;
    }

    $sql = file_get_contents($caminho);

    $pdo->beginTransaction();

    try {
        $pdo->exec($sql);

        $registro = $pdo->prepare('INSERT INTO migracoes_executadas (arquivo) VALUES (:arquivo)');
        $registro->execute(['arquivo' => $nome]);

        $pdo->commit();
        echo "Aplicada: {$nome}\n";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "ERRO ao aplicar {$nome}: " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo "Migracoes concluidas.\n";
