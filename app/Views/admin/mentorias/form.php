<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Novo horário de mentoria — <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('mentoriaAdmin/novo/' . (int) $concurso['id']); ?>">
    <label>Mentor:
        <select name="mentor_usuario_id" required>
            <?php foreach ($mentores as $mentor): ?>
                <option value="<?php echo (int) $mentor['id']; ?>" <?php echo (int) $mentor['id'] === (int) \App\Core\Auth::usuarioId() ? 'selected' : ''; ?>><?php echo htmlspecialchars($mentor['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
        </select>
    </label><br>

    <label>Início:
        <input type="datetime-local" name="data_inicio" required>
    </label><br>

    <label>Fim:
        <input type="datetime-local" name="data_fim" required>
    </label><br>

    <label>Observação (opcional — foco/tema deste horário):
        <input type="text" name="observacao" maxlength="255" placeholder="Ex.: Mentoria técnica — arquitetura de software">
    </label>

    <div class="form-acoes">
        <a href="<?php echo url('mentoriaAdmin/index/' . (int) $concurso['id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>
