<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Prévia da distribuição automática — <?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('designacoes/index/' . (int) $etapa['id']); ?>">Voltar às designações</a></p>

<p>Revise as sugestões abaixo — troque o avaliador em qualquer linha antes de confirmar. Nada é gravado até você clicar em "Confirmar distribuição".</p>

<form method="post" action="<?php echo url('designacoes/confirmarDistribuicao'); ?>">
    <input type="hidden" name="etapa_id" value="<?php echo (int) $etapa['id']; ?>">

    <table border="1" cellpadding="6">
        <tr><th>Submissão</th><th>Equipe</th><th>Avaliador sugerido</th></tr>
        <?php foreach ($linhas as $linha): ?>
        <tr>
            <td>
                #<?php echo (int) $linha['submissao_id']; ?>
                <input type="hidden" name="submissao_id[]" value="<?php echo (int) $linha['submissao_id']; ?>">
            </td>
            <td><?php echo htmlspecialchars($linha['nome_equipe'] !== null ? $linha['nome_equipe'] : '—', ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <select name="usuario_id[]">
                    <?php foreach ($linha['candidatos'] as $candidato): ?>
                        <option value="<?php echo (int) $candidato['id']; ?>" <?php echo $candidato['id'] === $linha['sugerido_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($candidato['nome'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <button type="submit">Confirmar distribuição</button>
</form>
