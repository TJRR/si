<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Editar equipe</h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('participante/editarEquipe'); ?>">
    <label>Nome da equipe:
        <input type="text" name="nome_equipe" required value="<?php echo htmlspecialchars($equipe['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Vínculo institucional:
        <input type="text" name="vinculo_institucional" value="<?php echo htmlspecialchars((string) $equipe['vinculo_institucional'], ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Observações:<br>
        <textarea name="observacoes" rows="4" cols="50"><?php echo htmlspecialchars((string) $equipe['observacoes'], ENT_QUOTES, 'UTF-8'); ?></textarea>
    </label><br>

    <div class="form-acoes">
        <a href="<?php echo url('participante/index'); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>
