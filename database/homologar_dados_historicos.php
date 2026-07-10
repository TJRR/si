<?php

/**
 * Trata os 128 vinculos equipe_participante ja existentes (importados via
 * script CSV para a edicao 2026, antes do sistema existir) como se tivessem
 * seguido o fluxo normal de inscricao: marca status_homologacao='homologado'.
 *
 * NAO cria conta de acesso (usuarios/perfil "participante") para essas 378
 * pessoas nem dispara e-mail - isso e uma decisao separada; se quiser liberar
 * acesso a algum desses participantes historicos, use a tela de Homologacao
 * (ela so lista pendentes, entao apos este script ninguem aparecera la para
 * essas equipes - liberacao de acesso teria que ser manual/futura).
 *
 * Idempotente: so atualiza linhas ainda 'pendente'.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

$pdo = Database::conexao();

$stmt = $pdo->prepare(
    "UPDATE equipe_participante
     SET status_homologacao = 'homologado', homologado_em = NOW()
     WHERE status_homologacao = 'pendente'"
);
$stmt->execute();

echo $stmt->rowCount() . " vinculo(s) equipe_participante marcado(s) como homologado (dado historico da edicao 2026).\n";
