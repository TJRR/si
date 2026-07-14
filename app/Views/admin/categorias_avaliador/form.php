<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1><?php echo $categoria === null ? 'Nova categoria' : 'Editar categoria'; ?> — <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo $categoria === null ? url('categoriasAvaliador/novo/' . (int) $concurso['id']) : url('categoriasAvaliador/editar/' . (int) $categoria['id']); ?>">
    <label>Nome (ex.: Professor, Área finalística/meio, TI):
        <input type="text" name="nome" required value="<?php echo htmlspecialchars($categoria !== null ? $categoria['nome'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <div class="form-acoes">
        <a href="<?php echo url('categoriasAvaliador/index/' . (int) $concurso['id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>
