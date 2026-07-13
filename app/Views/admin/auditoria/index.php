<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Auditoria</h1>

<p><a href="<?php echo url('home/administrativo'); ?>">Voltar ao painel</a></p>

<form method="get" action="<?php echo config('base_path'); ?>/index.php">
    <input type="hidden" name="r" value="auditoria/index">
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
    <button type="submit">Filtrar</button>
    <a href="<?php echo url('auditoria/index'); ?>">Limpar filtros</a>
</form>

<?php if (empty($registros)): ?>
    <p>Nenhum registro encontrado.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr>
            <th>Horário</th>
            <th>Usuário</th>
            <th>Ação</th>
            <th>Entidade</th>
            <th>ID</th>
            <th>IP</th>
            <th>Mensagem</th>
            <th>Detalhes</th>
        </tr>
        <?php foreach ($registros as $indice => $registro): ?>
        <tr>
            <td><?php echo htmlspecialchars(date('d/m/Y H:i:s', strtotime($registro['criado_em'])), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($registro['usuario_nome'] !== null ? $registro['usuario_nome'] : 'Sistema', ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($registro['acao'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($registro['entidade'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo $registro['entidade_id'] !== null ? (int) $registro['entidade_id'] : '—'; ?></td>
            <td><?php echo htmlspecialchars($registro['ip_origem'] !== null ? $registro['ip_origem'] : '—', ENT_QUOTES, 'UTF-8'); ?></td>
            <td title="<?php echo htmlspecialchars((string) $registro['mensagem'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($registro['mensagem'] !== null ? mb_strimwidth($registro['mensagem'], 0, 60, '...') : '—', ENT_QUOTES, 'UTF-8'); ?>
            </td>
            <td>
                <?php if ($registro['dados_antes'] !== null || $registro['dados_depois'] !== null): ?>
                    <button type="button" class="btn-secundario auditoria-ver-detalhes" data-alvo="auditoria-detalhes-<?php echo (int) $indice; ?>">Ver</button>
                <?php else: ?>
                    —
                <?php endif; ?>
            </td>
        </tr>
        <?php if ($registro['dados_antes'] !== null || $registro['dados_depois'] !== null): ?>
        <tr id="auditoria-detalhes-<?php echo (int) $indice; ?>" class="auditoria-detalhes-linha" style="display:none;">
            <td colspan="8">
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

    <?php
        $paramsFiltro = array_filter([
            'usuario_id' => $filtros['usuario_id'],
            'acao' => $filtros['acao'],
            'data_inicio' => $filtros['data_inicio'],
            'data_fim' => $filtros['data_fim'],
        ], function ($valor) {
            return $valor !== null && $valor !== '';
        });
    ?>
    <p>
        Página <?php echo (int) $pagina; ?> de <?php echo (int) $totalPaginas; ?>
        <?php if ($pagina > 1): ?>
            | <a href="<?php echo config('base_path'); ?>/index.php?<?php echo htmlspecialchars(http_build_query($paramsFiltro + ['r' => 'auditoria/index', 'pagina' => $pagina - 1]), ENT_QUOTES, 'UTF-8'); ?>">&larr; Anterior</a>
        <?php endif; ?>
        <?php if ($pagina < $totalPaginas): ?>
            | <a href="<?php echo config('base_path'); ?>/index.php?<?php echo htmlspecialchars(http_build_query($paramsFiltro + ['r' => 'auditoria/index', 'pagina' => $pagina + 1]), ENT_QUOTES, 'UTF-8'); ?>">Próxima &rarr;</a>
        <?php endif; ?>
    </p>
<?php endif; ?>

<script>
(function () {
    var botoes = document.querySelectorAll('.auditoria-ver-detalhes');

    botoes.forEach(function (botao) {
        botao.addEventListener('click', function () {
            var linha = document.getElementById(botao.getAttribute('data-alvo'));
            linha.style.display = linha.style.display === 'none' ? '' : 'none';
        });
    });
})();
</script>
