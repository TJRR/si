<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Configurações de Estandes: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('estandes/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<?php if (!$podeEditar): ?>
    <p class="status-pill laranja">Somente leitura: só o Administrador altera estas configurações.</p>
<?php endif; ?>

<form method="post" action="<?php echo url('estandes/configuracoes/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
    <fieldset>
        <legend>Texto do convite ao representante</legend>
        <p style="color:#555;font-size:0.9em;">O e-mail de convite sempre traz a saudação, o nome do estande e do evento, o que o representante pode fazer no sistema e como acessar (endereço para definir a senha, para quem ainda não tem conta, ou aviso para entrar com o acesso de sempre). O texto abaixo entra logo depois da apresentação, em todos os convites deste evento. Em branco, vai só o texto padrão.</p>
        <?php if ($podeEditar): ?>
            <?php
            $nome = 'mensagem_convite_html';
            $valor = $config !== null ? (string) $config['mensagem_convite_html'] : '';
            $rotulo = null;
            include __DIR__ . '/../_editor_rico.php';
            ?>
        <?php else: ?>
            <div><?php echo $config !== null && trim((string) $config['mensagem_convite_html']) !== '' ? sanitizarHtmlRico((string) $config['mensagem_convite_html']) : '<em>Sem texto adicional.</em>'; ?></div>
        <?php endif; ?>
    </fieldset>

    <?php if ($podeEditar): ?>
    <div class="form-acoes">
        <button type="submit">Salvar</button>
    </div>
    <?php endif; ?>
</form>
