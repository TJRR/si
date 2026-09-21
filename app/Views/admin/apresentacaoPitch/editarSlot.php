<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Editar horário: <?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if (!empty($erro)): ?>
    <p class="flash-mensagem vermelho"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('apresentacaoPitchAdmin/editarSlot/' . (int) $slot['id']); ?>"><?= campoCsrf() ?>
    <label>Início:
        <input type="datetime-local" name="data_inicio" required value="<?php echo htmlspecialchars(str_replace(' ', 'T', substr((string) $slot['data_inicio'], 0, 16)), ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Fim:
        <input type="datetime-local" name="data_fim" required value="<?php echo htmlspecialchars(str_replace(' ', 'T', substr((string) $slot['data_fim'], 0, 16)), ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <div class="form-acoes">
        <a href="<?php echo url('apresentacaoPitchAdmin/index/' . (int) $etapa['id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>
