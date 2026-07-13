<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Configurações</h1>

<p><a href="<?php echo url('home/administrativo'); ?>">Voltar ao painel</a></p>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('configuracoes/index'); ?>">
    <label>Tempo de expiração de sessão por inatividade (minutos):
        <input type="number" name="sessao_timeout_minutos" min="1" value="<?php echo (int) $configuracao['sessao_timeout_minutos']; ?>">
    </label><br>

    <button type="submit">Salvar</button>
</form>
