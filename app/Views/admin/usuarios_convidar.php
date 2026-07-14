<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
$todasCategorias = [];
foreach ($concursos as $concurso) {
    foreach (isset($categoriasPorConcurso[(int) $concurso['id']]) ? $categoriasPorConcurso[(int) $concurso['id']] : [] as $categoria) {
        $todasCategorias[] = ['id' => $categoria['id'], 'nome' => $categoria['nome'], 'concurso_nome' => $concurso['nome']];
    }
}
?>
<h1>Convidar usuário</h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('usuarios/convidar'); ?>">
    <label>Nome:
        <input type="text" name="nome" required>
    </label><br>

    <label>E-mail:
        <input type="email" name="email" required>
    </label><br>

    <label>Perfil:
        <select name="perfil" id="campo-perfil-convite" required>
            <option value="">Perfil...</option>
            <?php foreach ($perfis as $perfil): ?>
                <option value="<?php echo htmlspecialchars($perfil['chave'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($perfil['nome_exibicao'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label><br>

    <label>Concurso:
        <select name="concurso_id">
            <option value="">Global (todos os concursos)</option>
            <?php foreach ($concursos as $concurso): ?>
                <option value="<?php echo (int) $concurso['id']; ?>">
                    <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label><br>

    <span id="campo-categoria-wrapper-convite">
        <label>Categoria de avaliador (precisa bater com o concurso escolhido acima):
            <select name="categoria_avaliador_id">
                <option value="">Sem categoria de avaliador</option>
                <?php foreach ($todasCategorias as $categoria): ?>
                    <option value="<?php echo (int) $categoria['id']; ?>">
                        <?php echo htmlspecialchars($categoria['nome'] . ' — ' . $categoria['concurso_nome'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label><br>
    </span>

    <p>Se o e-mail já tiver cadastro, só o perfil escolhido acima é adicionado — nenhum e-mail novo é enviado.</p>

    <div class="form-acoes">
        <a href="<?php echo url('usuarios/index'); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Convidar</button>
    </div>
</form>

<script>
(function () {
    var select = document.getElementById('campo-perfil-convite');
    var wrapper = document.getElementById('campo-categoria-wrapper-convite');

    function atualizar() {
        wrapper.style.display = select.value === 'avaliador' ? '' : 'none';
    }

    select.addEventListener('change', atualizar);
    atualizar();
})();
</script>
