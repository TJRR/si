<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Configurações</h1>
    <div class="pagina-titulo-botoes">
        <button type="submit" form="form-configuracoes">Salvar</button>
        <a href="<?php echo url('home/administrativo'); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('configuracoes/index'); ?>" id="form-configuracoes">
    <label>Tempo de expiração de sessão por inatividade (minutos):
        <input type="number" name="sessao_timeout_minutos" min="1" value="<?php echo (int) $configuracao['sessao_timeout_minutos']; ?>">
    </label><br>
</form>
