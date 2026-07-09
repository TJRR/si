<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Tema</h1>

<?php if (!empty($_SESSION['flash'])): ?>
    <p style="color:red;"><?php echo htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['flash']); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('tema/index'); ?>">
    <fieldset>
        <legend>Cores</legend>
        <p>
            <label>
                Cor primaria (inicio do degrade)<br>
                <input type="color" name="cor_primaria_inicio"
                       value="<?php echo htmlspecialchars($configuracaoVisual['cor_primaria_inicio'], ENT_QUOTES, 'UTF-8'); ?>">
            </label>
        </p>
        <p>
            <label>
                Cor primaria (fim do degrade)<br>
                <input type="color" name="cor_primaria_fim"
                       value="<?php echo htmlspecialchars($configuracaoVisual['cor_primaria_fim'], ENT_QUOTES, 'UTF-8'); ?>">
            </label>
        </p>
    </fieldset>

    <button type="submit">Salvar</button>
</form>
