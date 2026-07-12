<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Trocar líder da equipe</h1>

<p><a href="<?php echo url('participante/minhaEquipe'); ?>">Voltar para minha equipe</a></p>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php if (empty($colegas)): ?>
    <p>Não há outros integrantes homologados para assumir a liderança.</p>
<?php else: ?>
    <form method="post" action="<?php echo url('participante/trocarLider'); ?>">
        <p>Selecione o novo líder (somente integrantes homologados podem ser escolhidos):</p>
        <?php foreach ($colegas as $colega): ?>
            <label>
                <input type="radio" name="novo_lider_id" value="<?php echo (int) $colega['id']; ?>" <?php echo $colega['papel'] === 'lider' ? 'checked' : ''; ?>>
                <?php echo htmlspecialchars($colega['nome'], ENT_QUOTES, 'UTF-8'); ?>
                <?php echo $colega['papel'] === 'lider' ? ' (líder atual)' : ''; ?>
            </label><br>
        <?php endforeach; ?>
        <br>
        <button type="submit">Confirmar novo líder</button>
    </form>
<?php endif; ?>
