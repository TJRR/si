<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Novo critério de desempate — <?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('desempate/index/' . (int) $trilha['id']); ?>">Voltar</a></p>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php if (empty($criteriosDisponiveis)): ?>
    <p>Nenhum critério cadastrado nas etapas desta trilha ainda. Cadastre os critérios antes de definir o desempate.</p>
<?php else: ?>
    <form method="post" action="<?php echo url('desempate/novo/' . (int) $trilha['id']); ?>">
        <label>Critério (etapa — nome):
            <select name="criterio_avaliacao_id">
                <?php foreach ($criteriosDisponiveis as $criterio): ?>
                    <option value="<?php echo (int) $criterio['id']; ?>">
                        <?php echo htmlspecialchars($criterio['etapa_nome'] . ' — ' . $criterio['criterio_nome'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label><br>

        <label>Direção:
            <select name="direcao">
                <option value="desc">Decrescente (maior nota vence)</option>
                <option value="asc">Crescente (menor nota vence)</option>
            </select>
        </label><br>

        <button type="submit">Adicionar</button>
    </form>
<?php endif; ?>
