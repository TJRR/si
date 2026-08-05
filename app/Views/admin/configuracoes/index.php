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

<h2>Modo de manutenção</h2>
<?php if (isset($configuracao['sistema_desativado']) && (int) $configuracao['sistema_desativado'] === 1): ?>
    <p><strong style="color:red;">Sistema em manutenção.</strong> Apenas administradores conseguem acessar; todos os outros usuários são desconectados na próxima ação que tentarem.</p>
    <form method="post" action="<?php echo url('configuracoes/reativarSistema'); ?>" onsubmit="return confirm('Reativar o sistema? O acesso normal volta para todos os usuários imediatamente.');">
        <button type="submit">Reativar sistema</button>
    </form>
<?php else: ?>
    <p>Sistema ativo, funcionando normalmente para todos os perfis.</p>
    <form method="post" action="<?php echo url('configuracoes/desativarSistema'); ?>" onsubmit="return confirm('Desativar o sistema? Isso vai bloquear o acesso e desconectar TODOS os usuários, exceto administradores, até que o sistema seja reativado. Use apenas durante uma atualização.');">
        <button type="submit">Desativar sistema</button>
    </form>
<?php endif; ?>
