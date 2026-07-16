<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Meu perfil</h1>

<?php if (!empty($flash)): ?>
    <p style="color:green;"><?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php if (!empty($usuario['foto_path'])): ?>
    <p><img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $usuario['foto_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="Foto de perfil" width="120" height="120" style="border-radius:50%;object-fit:cover;"></p>
<?php endif; ?>

<form method="post" action="<?php echo url('meuPerfil/index'); ?>" enctype="multipart/form-data">
    <label>Nome completo:
        <input type="text" name="nome" value="<?php echo htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8'); ?>" required>
    </label><br>

    <label>Foto de perfil (JPG, PNG, WEBP ou GIF, até 4MB):
        <input type="file" name="foto" accept="image/*">
    </label><br>

    <p>E-mail: <?php echo htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8'); ?> (não editável)</p>

    <div class="form-acoes">
        <a href="<?php echo url($destinoPainel); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>

<?php if (!empty($podeVisualizarComo)): ?>
    <h2>Visualizar como outro usuário</h2>
    <p>Somente leitura: o que você salvar enquanto visualiza como outro usuário não é gravado. Use para dar suporte técnico ou identificar problemas relatados por um usuário.</p>

    <form method="post" action="<?php echo url('meuPerfil/visualizarComo'); ?>">
        <label>Usuário:
            <input type="text" name="usuario_id" list="lista-usuarios-visualizar" placeholder="Digite o nome ou e-mail...">
        </label>
        <datalist id="lista-usuarios-visualizar">
            <?php foreach ($usuariosParaVisualizar as $usuarioOpcao): ?>
                <option value="<?php echo (int) $usuarioOpcao['id']; ?>"><?php echo htmlspecialchars($usuarioOpcao['nome'] . ' (' . $usuarioOpcao['email'] . ')', ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
        </datalist>
        <button type="submit">Visualizar como</button>
    </form>
<?php endif; ?>
