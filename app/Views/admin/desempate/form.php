<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Novo critério de desempate — <?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?> — <?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('desempate/novo/' . (int) $trilha['id'] . '/' . (int) $etapa['id']); ?>">
    <label>Tipo:
        <select name="tipo">
            <option value="criterio">Nota de um critério</option>
            <option value="data_submissao">Data de inscrição (quem enviou primeiro)</option>
        </select>
    </label><br>

    <?php if (empty($criteriosDisponiveis)): ?>
        <p>Nenhum critério cadastrado nesta etapa ainda (só necessário se o tipo escolhido acima for "Nota de um critério").</p>
    <?php else: ?>
        <label>Critério (só usado se o tipo for "Nota de um critério"):
            <select name="criterio_avaliacao_id">
                <?php foreach ($criteriosDisponiveis as $criterio): ?>
                    <option value="<?php echo (int) $criterio['id']; ?>">
                        <?php echo htmlspecialchars($criterio['criterio_nome'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label><br>
    <?php endif; ?>

    <label>Direção:
        <select name="direcao">
            <option value="desc">Decrescente (maior valor vence)</option>
            <option value="asc">Crescente (menor valor vence — use esta opção para "Data de inscrição")</option>
        </select>
    </label><br>

    <div class="form-acoes">
        <a href="<?php echo url('desempate/index/' . (int) $trilha['id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Adicionar</button>
    </div>
</form>
