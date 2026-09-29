<?php

/**
 * Fase 55: desfaz UMA conexao entre dois participantes de um Evento.
 *
 * Existe porque a tela "Ler codigo" deixou de ser so' conferencia e passou a
 * gravar conexao e creditar pontos: uma leitura feita por engano (por
 * exemplo, quem conferia cracha na portaria) precisa de um caminho de
 * correcao. Nao e' funcionalidade do sistema, e sim rotina rara de operacao,
 * por isso nao tem tela - mesmo criterio de database/excluir_usuario.php.
 *
 * Apaga a linha de evento_conexoes, o que devolve os pontos daquela conexao
 * aos dois lados (o total de cada pessoa e' sempre somado das conexoes que
 * existem). Nenhum outro dado e' tocado.
 *
 * Uso:
 *   php database/remover_conexao_evento.php <id_da_conexao>               (consulta - so mostra o que seria removido)
 *   php database/remover_conexao_evento.php <id_da_conexao> --confirmar    (remove de verdade)
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
$conexaoId = 0;

foreach (array_slice($argv, 1) as $argumento) {
    if ($argumento !== '--confirmar' && ctype_digit($argumento)) {
        $conexaoId = (int) $argumento;
        break;
    }
}

if ($conexaoId <= 0) {
    echo "Uso: php database/remover_conexao_evento.php <id_da_conexao> [--confirmar]\n";
    exit(1);
}

$pdo = Database::conexao();

$stmt = $pdo->prepare(
    'SELECT c.*, e.nome AS evento_nome,
            um.nome AS nome_menor, uma.nome AS nome_maior
     FROM evento_conexoes c
     JOIN eventos e ON e.id = c.evento_id
     JOIN evento_inscricoes im ON im.id = c.inscricao_menor_id
     JOIN usuarios um ON um.id = im.usuario_id
     JOIN evento_inscricoes ima ON ima.id = c.inscricao_maior_id
     JOIN usuarios uma ON uma.id = ima.usuario_id
     WHERE c.id = :id LIMIT 1'
);
$stmt->execute(['id' => $conexaoId]);
$conexao = $stmt->fetch();

if ($conexao === false) {
    echo "Conexao {$conexaoId} nao encontrada.\n";
    exit(1);
}

echo "Conexao {$conexaoId}\n";
echo str_repeat('-', 60) . "\n";
echo '  - evento: ' . $conexao['evento_nome'] . "\n";
echo '  - pessoas: ' . $conexao['nome_menor'] . ' e ' . $conexao['nome_maior'] . "\n";
echo '  - pontos creditados: ' . (int) $conexao['pontos_creditados_menor']
    . ' e ' . (int) $conexao['pontos_creditados_maior'] . "\n";
echo '  - conectada em: ' . $conexao['conectado_em'] . "\n";

if (!$confirmar) {
    echo "\nModo consulta. Nada foi alterado.\n";
    echo "Ao confirmar, a conexao e' removida e os pontos dela deixam de contar para as duas pessoas.\n";
    echo "Para aplicar de verdade, repita o comando com --confirmar.\n";
    exit;
}

$exclusao = $pdo->prepare('DELETE FROM evento_conexoes WHERE id = :id');
$exclusao->execute(['id' => $conexaoId]);

// Auditoria so' com numeros, como a gravacao da conexao: nome nenhum entra
// na trilha. O usuario_id fica nulo porque nao ha sessao na linha de comando.
Auditoria::registrar('remover_conexao', 'evento_conexoes', $conexaoId, [
    'evento_id' => (int) $conexao['evento_id'],
    'inscricao_menor_id' => (int) $conexao['inscricao_menor_id'],
    'inscricao_maior_id' => (int) $conexao['inscricao_maior_id'],
    'pontos_creditados_menor' => (int) $conexao['pontos_creditados_menor'],
    'pontos_creditados_maior' => (int) $conexao['pontos_creditados_maior'],
], null, 'Removida por linha de comando (database/remover_conexao_evento.php).');

echo "\nConexao {$conexaoId} removida.\n";
