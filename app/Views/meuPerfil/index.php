<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Meu perfil</h1>

<?php if (!empty($erro)): ?>
    <p class="flash-mensagem erro"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('meuPerfil/index'); ?>" enctype="multipart/form-data"><?= campoCsrf() ?>
    <section class="admin-card perfil-identidade">
        <?php if (!empty($usuario['foto_path'])): ?>
            <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $usuario['foto_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="Foto de perfil" class="perfil-avatar">
        <?php else: ?>
            <span class="perfil-avatar perfil-avatar-iniciais" aria-hidden="true"><?php echo htmlspecialchars(iniciaisAvatar($usuario['nome']), ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>

        <div class="perfil-identidade-dados">
            <strong class="perfil-identidade-nome"><?php echo htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
            <span class="perfil-identidade-email"><?php echo htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8'); ?></span>

            <label class="btn-acao perfil-foto-botao">
                Trocar foto
                <input type="file" name="foto" accept="image/*" class="perfil-foto-input" onchange="document.getElementById('perfil-foto-nome').textContent = this.files.length ? this.files[0].name : '';">
            </label>

            <span class="perfil-dica">JPG, PNG, WEBP ou GIF · Máx. 4 MB<span id="perfil-foto-nome" class="perfil-foto-nome"></span></span>
        </div>
    </section>

    <section class="admin-card perfil-card">
        <label>Nome completo
            <input type="text" name="nome" value="<?php echo htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </label>

        <label>E-mail
            <input type="text" value="<?php echo htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8'); ?>" disabled>
        </label>
        <p class="perfil-dica">O e-mail não pode ser alterado.</p>
    </section>

    <section class="admin-card perfil-card">
        <h2>Dados complementares</h2>
        <p class="perfil-dica">Usados quando você é designado facilitador de uma atividade (instrutor, professor, palestrante), para não precisar redigitar a cada nova designação.</p>

        <label>Tipo de documento
            <select name="tipo_documento">
                <?php foreach (\App\Repositories\UsuarioPerfilRepository::TIPOS_DOCUMENTO as $tipo): ?>
                    <option value="<?php echo htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($perfil !== null && $perfil['tipo_documento'] === $tipo) ? 'selected' : ''; ?>><?php echo htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Documento
            <input type="text" name="documento" value="<?php echo htmlspecialchars($perfil !== null ? (string) $perfil['documento'] : '', ENT_QUOTES, 'UTF-8'); ?>" size="20">
        </label>

        <label>Cargo
            <input type="text" name="cargo" maxlength="150" value="<?php echo htmlspecialchars($perfil !== null ? (string) $perfil['cargo'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </label>

        <label>Categoria profissional
            <select name="categoria_profissional">
                <option value="">Selecione</option>
                <?php foreach (\App\Repositories\UsuarioPerfilRepository::CATEGORIAS_PROFISSIONAIS as $categoria): ?>
                    <option value="<?php echo htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($perfil !== null && $perfil['categoria_profissional'] === $categoria) ? 'selected' : ''; ?>><?php echo htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Tribunal ou outro órgão de origem
            <input type="text" name="orgao_origem" maxlength="150" value="<?php echo htmlspecialchars($perfil !== null ? (string) $perfil['orgao_origem'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </label>

        <fieldset>
            <legend>Minicurrículo</legend>
            <textarea name="minicurriculo" rows="4" cols="60"><?php echo htmlspecialchars($perfil !== null ? (string) $perfil['minicurriculo'] : '', ENT_QUOTES, 'UTF-8'); ?></textarea>
        </fieldset>

        <div class="form-acoes">
            <button type="submit">Salvar</button>
            <a href="<?php echo url($destinoPainel); ?>" class="btn-voltar">Voltar</a>
        </div>
    </section>
</form>
