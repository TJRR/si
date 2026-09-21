<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Regras de desempate: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('trabalhos/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<p style="color:#555;font-size:0.9em;">Aplicadas em cascata, na ordem cadastrada, só quando a nota final empatar entre dois trabalhos.</p>

<?php if (empty($regrasDesempate)): ?>
    <p>Nenhuma regra de desempate cadastrada ainda.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Ordem</th><th>Critério de desempate</th><th>Direção</th><th>Ações</th></tr>
        <?php foreach ($regrasDesempate as $regra): ?>
        <tr>
            <td><?php echo (int) $regra['ordem'] + 1; ?></td>
            <td><?php echo $regra['tipo'] === 'data_submissao' ? 'Data de submissão mais antiga' : 'Maior nota no critério "' . htmlspecialchars((string) $regra['criterio_nome'], ENT_QUOTES, 'UTF-8') . '"'; ?></td>
            <td><?php echo $regra['direcao'] === 'asc' ? 'Crescente' : 'Decrescente'; ?></td>
            <td>
                <form method="post" action="<?php echo url('trabalhos/desempateRemover/' . (int) $regra['id']); ?>" onsubmit="return confirm('Remover esta regra de desempate?');"><?= campoCsrf() ?>
                    <button type="submit">Remover</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<h2>Nova regra</h2>
<form method="post" action="<?php echo url('trabalhos/desempateNovo/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
    <label>Tipo:
        <select name="tipo" id="trabalho-desempate-tipo">
            <option value="criterio">Maior nota em um critério</option>
            <option value="data_submissao">Data de submissão mais antiga</option>
        </select>
    </label>
    <label>Critério (só para o tipo "critério"):
        <select name="criterio_id">
            <?php foreach ($criterios as $criterio): ?>
                <option value="<?php echo (int) $criterio['id']; ?>"><?php echo htmlspecialchars($criterio['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Direção:
        <select name="direcao">
            <option value="desc">Maior valor vence</option>
            <option value="asc">Menor valor vence</option>
        </select>
    </label>
    <button type="submit">Adicionar regra</button>
</form>
