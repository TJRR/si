<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Perfis: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('eventos/editar/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<p style="color:#555;font-size:0.9em;">Perfis da equipe de organização deste evento (Instrutor, Professor, Palestrante, ou outro nome que preferir), usados ao vincular um facilitador a uma atividade.</p>

<?php if (empty($perfis)): ?>
    <p>Nenhum perfil cadastrado ainda.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Nome</th><th>Ações</th></tr>
        <?php foreach ($perfis as $perfil): ?>
        <tr>
            <td><?php echo htmlspecialchars($perfil['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <form method="post" action="<?php echo url('eventos/perfilRemover'); ?>"><?= campoCsrf() ?>
                    <input type="hidden" name="id" value="<?php echo (int) $perfil['id']; ?>">
                    <button type="submit" class="btn-icone" title="Remover">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<h2>Novo perfil</h2>
<form method="post" action="<?php echo url('eventos/perfilCriar/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
    <label>Nome do perfil:
        <input type="text" name="nome" required maxlength="100" size="30" placeholder="Ex.: Instrutor">
    </label>
    <button type="submit">Cadastrar</button>
</form>
