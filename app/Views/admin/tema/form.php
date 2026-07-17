<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
$corInicioAtual = $configuracaoVisual !== null && !empty($configuracaoVisual['cor_primaria_inicio']) ? $configuracaoVisual['cor_primaria_inicio'] : '#FF6600';
$corFimAtual = $configuracaoVisual !== null && !empty($configuracaoVisual['cor_primaria_fim']) ? $configuracaoVisual['cor_primaria_fim'] : '#FF9955';
$corSecundariaAtual = $configuracaoVisual !== null && !empty($configuracaoVisual['cor_secundaria']) ? $configuracaoVisual['cor_secundaria'] : '#191919';
$acaoForm = $concurso !== null ? url('tema/index/' . (int) $concurso['id']) : url('tema/index');
?>
<div class="pagina-titulo-acoes">
    <h1>Tema<?php echo $concurso !== null ? ' — ' . htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8') : ''; ?></h1>
    <div class="pagina-titulo-botoes">
        <button type="submit" form="form-tema">Salvar</button>
        <a href="<?php echo $concurso !== null ? url('concursos/index') : url('home/administrativo'); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<?php if ($concurso !== null): ?>
    <p>Esta é a identidade visual específica desta edição — sobrepõe o tema global apenas na home pública desta edição. Se nada for definido aqui, a home usa o tema global.</p>
<?php endif; ?>

<?php if (!empty($_SESSION['flash'])): ?>
    <p style="color:red;"><?php echo htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['flash']); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo $acaoForm; ?>" enctype="multipart/form-data" id="form-tema">
    <?php if ($concurso === null): ?>
    <fieldset>
        <legend>Logo padrão do sistema</legend>
        <?php if (!empty($configuracaoVisual['logo_path'])): ?>
            <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $configuracaoVisual['logo_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="Logo atual" style="max-width:200px;display:block;margin-bottom:.5rem;">
        <?php endif; ?>
        <label>
            Trocar logo:<br>
            <input type="file" name="logo" accept="image/*">
        </label>
    </fieldset>

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
    <?php else: ?>
    <fieldset>
        <legend>Logo desta edição (opcional)</legend>
        <?php if (!empty($configuracaoVisual['logo_path'])): ?>
            <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $configuracaoVisual['logo_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="Logo atual desta edição" style="max-width:200px;display:block;margin-bottom:.5rem;">
        <?php endif; ?>
        <label>
            Trocar logo (deixe em branco para manter/usar o logo global):<br>
            <input type="file" name="logo" accept="image/*">
        </label>
    </fieldset>
    <?php endif; ?>

    <fieldset>
        <legend>Cores</legend>
        <p>
            <label>
                Cor primária (início do degradê)<br>
                <input type="color" name="cor_primaria_inicio" value="<?php echo htmlspecialchars($corInicioAtual, ENT_QUOTES, 'UTF-8'); ?>">
            </label>
        </p>
        <p>
            <label>
                Cor primária (fim do degradê)<br>
                <input type="color" name="cor_primaria_fim" value="<?php echo htmlspecialchars($corFimAtual, ENT_QUOTES, 'UTF-8'); ?>">
            </label>
        </p>
        <p>
            <label>
                Cor secundária (ações secundárias — ex. "Reabrir"/"Voltar")<br>
                <input type="color" name="cor_secundaria" value="<?php echo htmlspecialchars($corSecundariaAtual, ENT_QUOTES, 'UTF-8'); ?>">
            </label>
        </p>
    </fieldset>
</form>
