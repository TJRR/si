<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
$corInicioAtual = $configuracaoVisual !== null && !empty($configuracaoVisual['cor_primaria_inicio']) ? $configuracaoVisual['cor_primaria_inicio'] : '#FF6600';
$corFimAtual = $configuracaoVisual !== null && !empty($configuracaoVisual['cor_primaria_fim']) ? $configuracaoVisual['cor_primaria_fim'] : '#FF9955';
$corSecundariaAtual = $configuracaoVisual !== null && !empty($configuracaoVisual['cor_secundaria']) ? $configuracaoVisual['cor_secundaria'] : '#191919';
?>
<div class="pagina-titulo-acoes">
    <h1>Tema</h1>
    <div class="pagina-titulo-botoes">
        <button type="submit" form="form-tema">Salvar</button>
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
            <?php $nome = 'cor_primaria_inicio'; $valor = $corInicioAtual; $rotulo = 'Cor primária (início do degradê)'; $permiteVazio = false; ?>
            <?php include __DIR__ . '/../_campo_cor.php'; ?>
        </p>
        <p>
            <?php $nome = 'cor_primaria_fim'; $valor = $corFimAtual; $rotulo = 'Cor primária (fim do degradê)'; $permiteVazio = false; ?>
            <?php include __DIR__ . '/../_campo_cor.php'; ?>
        </p>
        <p>
            <?php $nome = 'cor_secundaria'; $valor = $corSecundariaAtual; $rotulo = 'Cor secundária (ações secundárias — ex. "Reabrir"/"Voltar")'; $permiteVazio = false; ?>
            <?php include __DIR__ . '/../_campo_cor.php'; ?>
        </p>
    </fieldset>
</form>
