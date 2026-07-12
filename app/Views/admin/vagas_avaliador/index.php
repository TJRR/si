<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Vagas por categoria — <?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('etapas/index/' . (int) $trilha['id']); ?>">Voltar às etapas</a></p>

<?php if (!empty($flash)): ?>
    <p style="color:green;"><?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<p>Usada quando "Designação de avaliadores" desta etapa está em "Sorteio aleatório por categoria": defina quantos avaliadores de cada categoria são exigidos por submissão. Categorias com quantidade 0 (ou em branco) não entram no sorteio.</p>

<?php if (empty($categorias)): ?>
    <p><strong>Nenhuma categoria cadastrada para este concurso ainda.</strong>
        <a href="<?php echo url('categoriasAvaliador/index/' . (int) $trilha['concurso_id']); ?>">Cadastre categorias de avaliador</a> antes de configurar as vagas.</p>
<?php else: ?>
    <form method="post" action="<?php echo url('vagasAvaliador/index/' . (int) $etapa['id']); ?>">
        <table border="1" cellpadding="6">
            <tr><th>Categoria</th><th>Quantidade por submissão</th></tr>
            <?php foreach ($categorias as $categoria): ?>
            <tr>
                <td><?php echo htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <input type="number" min="0" name="quantidade[<?php echo (int) $categoria['id']; ?>]"
                        value="<?php echo isset($quantidadesAtuais[(int) $categoria['id']]) ? (int) $quantidadesAtuais[(int) $categoria['id']] : 0; ?>">
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <button type="submit">Salvar vagas</button>
    </form>
<?php endif; ?>
