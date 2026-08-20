<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Visualizar como outro usuário</h1>

<section class="admin-card perfil-card">
    <p class="perfil-aviso">Somente leitura: o que você salvar enquanto visualiza como outro usuário não é gravado. Use para dar suporte técnico ou identificar problemas relatados por um usuário.</p>

    <form method="post" action="<?php echo url('meuPerfil/visualizarComo'); ?>"><?= campoCsrf() ?>
        <label>Usuário
            <input type="text" name="usuario_id" list="lista-usuarios-visualizar" placeholder="Digite o nome ou e-mail...">
        </label>
        <datalist id="lista-usuarios-visualizar">
            <?php foreach ($usuariosParaVisualizar as $usuarioOpcao): ?>
                <option value="<?php echo (int) $usuarioOpcao['id']; ?>"><?php echo htmlspecialchars($usuarioOpcao['nome'] . ' (' . $usuarioOpcao['email'] . ')', ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
        </datalist>

        <div class="form-acoes">
            <button type="submit">Visualizar como</button>
            <a href="<?php echo url($destinoPainel); ?>" class="btn-voltar">Voltar</a>
        </div>
    </form>
</section>
