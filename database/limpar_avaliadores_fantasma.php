<?php

/**
 * Fase 21 (bugfix): lista e, com --confirmar, remove vinculos "fantasma" em
 * avaliador_categorias - linhas cujo usuario NAO tem mais o perfil
 * "avaliador" vivo em usuario_perfil_concurso para aquele concurso. Isso
 * acontecia porque UsuarioAdminController::salvarEdicao() trocava o perfil
 * do usuario (via PerfilRepository::substituirPerfil()) sem nunca limpar o
 * vinculo de categoria correspondente - a partir desta fase, a troca de
 * perfil ja limpa isso na origem (AvaliadorCategoriaRepository::
 * removerTodosVinculos()), mas vinculos antigos, criados antes da correcao,
 * continuam no banco ate alguem rodar este script.
 *
 * Nao e' necessario rodar isso para a correcao funcionar - a consulta
 * AvaliadorCategoriaRepository::listarUsuariosPorCategoria() ja ignora esses
 * vinculos na exibicao/sorteio. Este script e' so' higiene do dado (evitar
 * lixo permanente na tabela).
 *
 * Por padrao roda em modo consulta (dry-run): so mostra o que seria
 * removido. Para remover de verdade:
 *   php limpar_avaliadores_fantasma.php --confirmar
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Repositories\AvaliadorCategoriaRepository;

$confirmar = in_array('--confirmar', $argv, true);

$pdo = Database::conexao();
$stmt = $pdo->query(
    "SELECT ac.usuario_id, u.nome AS usuario_nome, ac.concurso_id, ca.nome AS categoria_nome
     FROM avaliador_categorias ac
     INNER JOIN usuarios u ON u.id = ac.usuario_id
     INNER JOIN categorias_avaliador ca ON ca.id = ac.categoria_avaliador_id
     LEFT JOIN usuario_perfil_concurso upc ON upc.usuario_id = ac.usuario_id
     LEFT JOIN perfis p ON p.id = upc.perfil_id AND p.chave = 'avaliador'
         AND (upc.concurso_id IS NULL OR upc.concurso_id = ac.concurso_id)
     WHERE p.id IS NULL
     ORDER BY u.nome ASC"
);
$fantasmas = $stmt->fetchAll();

if (empty($fantasmas)) {
    echo "Nenhum vinculo fantasma encontrado em avaliador_categorias.\n";
    exit(0);
}

echo "Vinculos fantasma encontrados (" . count($fantasmas) . "):\n";
foreach ($fantasmas as $item) {
    echo "  - {$item['usuario_nome']} (id {$item['usuario_id']}) - categoria: {$item['categoria_nome']} - concurso_id: {$item['concurso_id']}\n";
}

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi removido.\n";
    echo "Para remover de verdade, repita o comando com --confirmar\n";
    exit(0);
}

$repositorio = new AvaliadorCategoriaRepository();
$usuariosRemovidos = array_unique(array_column($fantasmas, 'usuario_id'));

foreach ($usuariosRemovidos as $usuarioId) {
    $repositorio->removerTodosVinculos($usuarioId);
}

echo "\nRemovido(s) vinculo(s) de " . count($usuariosRemovidos) . " usuário(s).\n";
