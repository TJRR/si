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
<h1>Editar usuário</h1>

<p><a href="<?php echo url('usuarios/index'); ?>">Voltar aos usuários</a></p>

<?php if (!empty($_SESSION['flash'])): ?>
    <p class="flash-mensagem <?php echo classeFlash(); ?>"><?php echo htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['flash']); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('usuarios/salvarEdicao'); ?>"><?= campoCsrf() ?>
    <input type="hidden" name="id" value="<?php echo (int) $usuario['id']; ?>">

    <label>Nome completo:
        <input type="text" name="nome" value="<?php echo htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8'); ?>" required>
    </label><br>

    <p>E-mail: <?php echo htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8'); ?> (não editável)</p>

    <?php
    /**
     * Fase 55 (pendência 24): quem tem um perfil que nasce por fluxo próprio
     * (Representante de estande, Inscrito em evento) não o encontra na lista,
     * porque ele não é atribuível por aqui. Sem este aviso, a seleção
     * apareceria em "Nenhum" e um "Salvar" descuidado pareceria ter apagado o
     * perfil da pessoa, quando na verdade nada muda.
     */
    $perfilAtualEhAtribuivel = $vinculoAtual === null
        || \App\Repositories\PerfilRepository::ehAtribuivelPelaTelaUsuarios($vinculoAtual['perfil']);
    ?>

    <?php if (!$perfilAtualEhAtribuivel): ?>
        <p class="status-pill laranja">
            Esta conta tem o perfil
            "<?php echo htmlspecialchars($vinculoAtual['perfil_nome'], ENT_QUOTES, 'UTF-8'); ?>", que só é
            atribuído pelo fluxo próprio dele e não aparece na lista abaixo. Salvar sem escolher um perfil
            mantém o atual; escolher outro substitui o que existe hoje.
        </p>
    <?php endif; ?>

    <label>Perfil:
        <select name="perfil" id="campo-perfil-editar">
            <option value="">Nenhum</option>
            <?php foreach ($perfis as $perfil): ?>
                <option value="<?php echo htmlspecialchars($perfil['chave'], ENT_QUOTES, 'UTF-8'); ?>"
                    <?php echo ($vinculoAtual !== null && $vinculoAtual['perfil'] === $perfil['chave']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($perfil['nome_exibicao'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label><br>

    <label>Concurso:
        <select name="concurso_id">
            <option value="">Global (todos os concursos)</option>
            <?php foreach ($concursos as $concurso): ?>
                <option value="<?php echo (int) $concurso['id']; ?>"
                    <?php echo ($vinculoAtual !== null && (int) $vinculoAtual['concurso_id'] === (int) $concurso['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label><br>

    <span id="campo-categoria-wrapper-editar">
        <label>Categoria de avaliador (precisa bater com o concurso escolhido acima):
            <select name="categoria_avaliador_id">
                <option value="">Sem categoria de avaliador</option>
                <?php foreach ($todasCategorias as $categoria): ?>
                    <option value="<?php echo (int) $categoria['id']; ?>"
                        <?php echo (!empty($vinculoAtual['categoria_atual']) && (int) $vinculoAtual['categoria_atual']['categoria_avaliador_id'] === (int) $categoria['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($categoria['nome'] . ': ' . $categoria['concurso_nome'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label><br>
    </span>

    <button type="submit">Salvar</button>
</form>

<p><em>Para revogar o acesso de um usuário, use "Suspender" na lista de Usuários.</em></p>

<script>
(function () {
    var select = document.getElementById('campo-perfil-editar');
    var wrapper = document.getElementById('campo-categoria-wrapper-editar');

    function atualizar() {
        wrapper.style.display = select.value === 'avaliador' ? '' : 'none';
    }

    select.addEventListener('change', atualizar);
    atualizar();
})();
</script>
