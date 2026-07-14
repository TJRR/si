<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Tema</h1>
    <div class="pagina-titulo-botoes">
        <button type="submit" form="form-tema">Salvar</button>
        <a href="<?php echo url('home/administrativo'); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<?php if (!empty($_SESSION['flash'])): ?>
    <p style="color:red;"><?php echo htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['flash']); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('tema/index'); ?>" enctype="multipart/form-data" id="form-tema">
    <fieldset>
        <legend>Favicon</legend>
        <p>
            Atual:
            <img src="<?php
                echo !empty($configuracaoVisual['favicon_path'])
                    ? htmlspecialchars(config('base_path') . '/assets/' . $configuracaoVisual['favicon_path'], ENT_QUOTES, 'UTF-8')
                    : htmlspecialchars(config('base_path') . '/assets/img/favicon-padrao.png', ENT_QUOTES, 'UTF-8');
            ?>" alt="Favicon atual" width="32" height="32">
        </p>
        <p>
            <label>
                Trocar favicon (PNG):<br>
                <input type="file" name="favicon" accept="image/png">
            </label>
        </p>
    </fieldset>

    <fieldset>
        <legend>Cores</legend>
        <p>
            <label>
                Cor primária (início do degradê)<br>
                <input type="color" name="cor_primaria_inicio"
                       value="<?php echo htmlspecialchars($configuracaoVisual['cor_primaria_inicio'], ENT_QUOTES, 'UTF-8'); ?>">
            </label>
        </p>
        <p>
            <label>
                Cor primária (fim do degradê)<br>
                <input type="color" name="cor_primaria_fim"
                       value="<?php echo htmlspecialchars($configuracaoVisual['cor_primaria_fim'], ENT_QUOTES, 'UTF-8'); ?>">
            </label>
        </p>
        <p>
            <label>
                Cor secundária (ações secundárias — ex. "Reabrir"/"Voltar")<br>
                <input type="color" name="cor_secundaria"
                       value="<?php echo htmlspecialchars($configuracaoVisual['cor_secundaria'], ENT_QUOTES, 'UTF-8'); ?>">
            </label>
        </p>
    </fieldset>
</form>
