<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Selecionar avaliadores do sorteio — <?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('designacoes/index/' . (int) $etapa['id']); ?>">Voltar às designações</a></p>

<p>Marque quem participa desta rodada de sorteio em cada categoria. Só os avaliadores marcados entram no sorteio; os demais ficam de fora desta rodada.</p>

<form method="post" action="<?php echo url('designacoes/distribuirComSelecao'); ?>">
    <input type="hidden" name="etapa_id" value="<?php echo (int) $etapa['id']; ?>">

    <?php foreach ($categorias as $categoria): ?>
        <fieldset>
            <legend><?php echo htmlspecialchars($categoria['categoria_nome'], ENT_QUOTES, 'UTF-8'); ?></legend>

            <?php if (empty($categoria['avaliadores'])): ?>
                <p>Nenhum avaliador vinculado a esta categoria ainda.</p>
            <?php else: ?>
                <?php foreach ($categoria['avaliadores'] as $avaliador): ?>
                    <label>
                        <input type="checkbox" name="avaliador_id[]" value="<?php echo (int) $avaliador['id']; ?>">
                        <?php echo htmlspecialchars($avaliador['nome'], ENT_QUOTES, 'UTF-8'); ?>
                    </label><br>
                <?php endforeach; ?>
            <?php endif; ?>
        </fieldset>
    <?php endforeach; ?>

    <div class="form-acoes">
        <a href="<?php echo url('designacoes/index/' . (int) $etapa['id']); ?>" class="btn-voltar">Cancelar</a>
        <button type="submit">Continuar</button>
    </div>
</form>
