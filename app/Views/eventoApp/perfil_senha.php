<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 55: troca de senha dentro do aplicativo do Evento. So' chega aqui
 * quem tem senha: quem entra pela conta Google e' desviado pelo controlador,
 * e a aba nem aparece.
 */
?>

<div class="site-page">
    <?php
    $eventoId = $evento['id'];
    $tituloTopo = $evento['nome'];
    $urlVoltar = url('eventoApp/index/' . (int) $eventoId);
    require __DIR__ . '/_app_bar.php';
    ?>

    <div class="site-form-page">
        <?php require __DIR__ . '/_ajuda_card.php'; ?>
        <h2>Alterar senha</h2>
        <?php require __DIR__ . '/_abas_perfil.php'; ?>

        <div class="admin-card">
            <form method="post" action="<?php echo url('eventoAppPerfil/senha/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
                <label>Senha atual
                    <input type="password" name="senha_atual" required autocomplete="current-password">
                </label>

                <label>Nova senha
                    <input type="password" name="senha_nova" minlength="8" required autocomplete="new-password">
                </label>
                <p><small>Mínimo de 8 caracteres.</small></p>

                <label>Confirme a nova senha
                    <input type="password" name="confirmacao" minlength="8" required autocomplete="new-password">
                </label>

                <div class="form-acoes">
                    <button type="submit">Alterar senha</button>
                    <a href="<?php echo url('eventoApp/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
                </div>
            </form>
        </div>
    </div>
</div>
