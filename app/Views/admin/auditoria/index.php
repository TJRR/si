<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Auditoria</h1>
    <a href="<?php echo url('home/administrativo'); ?>" class="btn-voltar">Voltar</a>
</div>

<div class="filtros-barra-wrapper">
    <form method="get" action="<?php echo config('base_path'); ?>/index.php" class="filtros-barra">
        <input type="hidden" name="r" value="auditoria/index">
        <label class="filtro-busca">Busca:
            <input type="text" name="busca" placeholder="Usuário, ação, IP..." value="<?php echo htmlspecialchars((string) $filtros['busca'], ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <label>Usuário:
            <select name="usuario_id">
                <option value="">Todos</option>
                <?php foreach ($usuarios as $usuario): ?>
                    <option value="<?php echo (int) $usuario['id']; ?>" <?php echo $filtros['usuario_id'] === (int) $usuario['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Ação:
            <select name="acao">
                <option value="">Todas</option>
                <?php foreach ($acoesDisponiveis as $acao): ?>
                    <option value="<?php echo htmlspecialchars($acao, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $filtros['acao'] === $acao ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($acao, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>De:
            <input type="date" name="data_inicio" value="<?php echo htmlspecialchars((string) $filtros['data_inicio'], ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <label>Até:
            <input type="date" name="data_fim" value="<?php echo htmlspecialchars((string) $filtros['data_fim'], ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <div class="filtros-barra-acoes">
            <button type="submit" class="btn-icone" title="Filtrar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                </svg>
            </button>
            <a href="<?php echo url('auditoria/index'); ?>" class="btn-icone" title="Limpar filtros">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="1 4 1 10 7 10"></polyline>
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                </svg>
            </a>
        </div>
    </form>
</div>

<?php
function auditoria_link_ordenar($rotulo, $coluna, $ordenar, $direcao, array $paramsFiltro)
{
    $novaDirecao = ($ordenar === $coluna && $direcao === 'asc') ? 'desc' : 'asc';
    $params = $paramsFiltro + ['r' => 'auditoria/index', 'ordenar' => $coluna, 'direcao' => $novaDirecao];

    $seta = '';
    if ($ordenar === $coluna) {
        $seta = $direcao === 'asc' ? ' ▲' : ' ▼';
    }

    return '<a href="' . config('base_path') . '/index.php?' . htmlspecialchars(http_build_query($params), ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8') . $seta . '</a>';
}

$paramsFiltro = array_filter([
    'busca' => $filtros['busca'],
    'usuario_id' => $filtros['usuario_id'],
    'acao' => $filtros['acao'],
    'data_inicio' => $filtros['data_inicio'],
    'data_fim' => $filtros['data_fim'],
], function ($valor) {
    return $valor !== null && $valor !== '';
});
$paramsCompletos = $paramsFiltro + ['ordenar' => $ordenar, 'direcao' => $direcao];
$urlExportar = url('auditoria/exportarCsv') . '&' . http_build_query($paramsCompletos);
?>

<div class="auditoria-resumo">
    <span><?php echo (int) $total; ?> registro<?php echo $total === 1 ? '' : 's'; ?> encontrado<?php echo $total === 1 ? '' : 's'; ?></span>
    <a href="<?php echo htmlspecialchars($urlExportar, ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Exportar CSV">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="7 10 12 15 17 10"></polyline>
            <line x1="12" y1="15" x2="12" y2="3"></line>
        </svg>
    </a>
</div>

<?php if (empty($registros)): ?>
    <p>Nenhum registro encontrado.</p>
<?php else: ?>
    <div class="tabela-scroll">
        <table>
            <tr>
                <th><?php echo auditoria_link_ordenar('Horário', 'criado_em', $ordenar, $direcao, $paramsFiltro); ?></th>
                <th><?php echo auditoria_link_ordenar('Usuário', 'usuario_nome', $ordenar, $direcao, $paramsFiltro); ?></th>
                <th><?php echo auditoria_link_ordenar('Ação', 'acao', $ordenar, $direcao, $paramsFiltro); ?></th>
                <th><?php echo auditoria_link_ordenar('Entidade', 'entidade', $ordenar, $direcao, $paramsFiltro); ?></th>
                <th><?php echo auditoria_link_ordenar('IP', 'ip_origem', $ordenar, $direcao, $paramsFiltro); ?></th>
                <th>Detalhes</th>
            </tr>
            <?php foreach ($registros as $indice => $registro): ?>
            <?php $temDetalhe = $registro['dados_antes'] !== null || $registro['dados_depois'] !== null || $registro['mensagem'] !== null; ?>
            <tr>
                <td><?php echo htmlspecialchars(date('d/m/Y H:i:s', strtotime($registro['criado_em'])), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($registro['usuario_nome'] !== null ? $registro['usuario_nome'] : 'Sistema', ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <span class="status-pill <?php echo categoriaAcaoAuditoria($registro['acao']); ?>">
                        <?php echo htmlspecialchars($registro['acao'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </td>
                <td><?php echo htmlspecialchars($registro['entidade'] . ($registro['entidade_id'] !== null ? ' #' . (int) $registro['entidade_id'] : ''), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($registro['ip_origem'] !== null ? $registro['ip_origem'] : '—', ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <?php if ($temDetalhe): ?>
                        <button type="button" class="btn-icone auditoria-ver-detalhes" data-alvo="auditoria-detalhes-<?php echo (int) $indice; ?>" title="Ver detalhes">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
            </tr>
            <?php if ($temDetalhe): ?>
            <tr id="auditoria-detalhes-<?php echo (int) $indice; ?>" class="auditoria-detalhes-linha" style="display:none;">
                <td colspan="6">
                    <?php if ($registro['mensagem'] !== null): ?>
                        <strong>Mensagem:</strong>
                        <p><?php echo htmlspecialchars($registro['mensagem'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                    <?php if ($registro['dados_antes'] !== null): ?>
                        <strong>Antes:</strong>
                        <pre><?php echo htmlspecialchars(json_encode(json_decode($registro['dados_antes']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?></pre>
                    <?php endif; ?>
                    <?php if ($registro['dados_depois'] !== null): ?>
                        <strong>Depois:</strong>
                        <pre><?php echo htmlspecialchars(json_encode(json_decode($registro['dados_depois']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?></pre>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
        </table>
    </div>

    <p class="auditoria-paginacao">
        <span>Página <?php echo (int) $pagina; ?> de <?php echo (int) $totalPaginas; ?></span>
        <?php if ($pagina > 1): ?>
            <a href="<?php echo config('base_path'); ?>/index.php?<?php echo htmlspecialchars(http_build_query($paramsCompletos + ['r' => 'auditoria/index', 'pagina' => $pagina - 1]), ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Página anterior">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </a>
        <?php endif; ?>
        <?php if ($pagina < $totalPaginas): ?>
            <a href="<?php echo config('base_path'); ?>/index.php?<?php echo htmlspecialchars(http_build_query($paramsCompletos + ['r' => 'auditoria/index', 'pagina' => $pagina + 1]), ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Próxima página">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </a>
        <?php endif; ?>
    </p>
<?php endif; ?>

<script>
(function () {
    var botoes = document.querySelectorAll('.auditoria-ver-detalhes');

    botoes.forEach(function (botao) {
        botao.addEventListener('click', function () {
            var linha = document.getElementById(botao.getAttribute('data-alvo'));
            var abrindo = linha.style.display === 'none';
            linha.style.display = abrindo ? '' : 'none';
            botao.classList.toggle('ativo', abrindo);
            botao.title = abrindo ? 'Ocultar detalhes' : 'Ver detalhes';
        });
    });
})();
</script>
