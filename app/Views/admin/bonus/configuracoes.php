<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Configurações dos bônus: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('bonus/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<?php if (!$podeEditar): ?>
    <p class="status-pill laranja">Somente leitura: só o Administrador altera estas configurações.</p>
<?php endif; ?>

<form method="post" action="<?php echo url('bonus/configuracoes/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
    <fieldset <?php echo $podeEditar ? '' : 'disabled'; ?>>
        <legend>Bônus automáticos</legend>

        <label>
            <input type="checkbox" name="ativo" value="1" <?php echo (int) $config['ativo'] === 1 ? 'checked' : ''; ?>>
            Ativar os bônus neste evento
        </label>
        <p style="color:#555;font-size:0.9em;">
            Com os bônus desativados, nada é apurado e o participante não vê o progresso no aplicativo.
            Os créditos já concedidos não são apagados nem perdem valor.
        </p>

        <p style="color:#555;font-size:0.9em;">
            Este evento tem <strong><?php echo (int) $totalBonus; ?></strong>
            <?php echo (int) $totalBonus === 1 ? 'bônus cadastrado' : 'bônus cadastrados'; ?>.
            O nome, o tipo, a exigência e os pontos de cada um ficam na aba
            <a href="<?php echo url('bonus/index/' . (int) $evento['id']); ?>">Bônus</a>.
        </p>

        <p style="color:#555;font-size:0.9em;">
            Salvar esta tela reconfere os bônus de todos os inscritos: quem já cumpria uma condição recebe
            o crédito na hora. A reconferência também pode ser acionada a qualquer momento pela aba
            <a href="<?php echo url('bonus/acompanhamento/' . (int) $evento['id']); ?>">Acompanhamento</a>.
        </p>
    </fieldset>

    <?php if ($podeEditar): ?>
    <div class="form-acoes">
        <button type="submit">Salvar</button>
    </div>
    <?php endif; ?>
</form>
