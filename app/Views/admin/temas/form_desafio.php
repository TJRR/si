<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1><?php echo $desafio === null ? 'Novo desafio' : 'Editar desafio'; ?> — <?php echo htmlspecialchars($tema['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php $somenteLeitura = !\App\Core\Auth::possuiPerfil('administrador'); $desabilitado = $somenteLeitura ? 'disabled' : ''; ?>
<form method="post" action="<?php echo $desafio === null ? url('temas/novoDesafio/' . (int) $tema['id']) : url('temas/editarDesafio/' . (int) $desafio['id']); ?>">
    <label>Pergunta do desafio (texto integral):<br>
        <textarea name="pergunta" rows="6" cols="80" required <?php echo $desabilitado; ?>><?php echo htmlspecialchars($desafio !== null ? $desafio['pergunta'] : '', ENT_QUOTES, 'UTF-8'); ?></textarea>
    </label><br>

    <label>
        <input type="checkbox" name="ativo" value="1" <?php echo ($desafio === null || $desafio['ativo']) ? 'checked' : ''; ?> <?php echo $desabilitado; ?>>
        Ativo
    </label><br>

    <div class="form-acoes">
        <a href="<?php echo url('temas/desafios/' . (int) $tema['id']); ?>" class="btn-voltar">Voltar</a>
        <?php if (!$somenteLeitura): ?>
        <button type="submit">Salvar</button>
        <?php endif; ?>
    </div>
</form>
