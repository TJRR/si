<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Homologação de inscrições — <?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('trilhas/index/' . (int) $trilha['concurso_id']); ?>">Voltar às trilhas</a></p>

<?php if (!empty($flash)): ?>
    <p style="color:green;"><?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php if (empty($pendentes)): ?>
    <p>Nenhuma inscrição pendente de homologação nesta trilha.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Equipe</th><th>Participante</th><th>Papel</th><th>CPF</th><th>E-mail</th><th>Telefone</th><th>Ações</th></tr>
        <?php foreach ($pendentes as $item): ?>
        <tr>
            <td><?php echo htmlspecialchars($item['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($item['participante_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo $item['papel'] === 'lider' ? 'Líder' : 'Integrante'; ?></td>
            <td><?php echo htmlspecialchars((string) $item['cpf'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string) $item['email'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string) $item['telefone'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <form method="post" action="<?php echo url('homologacao/homologar'); ?>" style="display:inline;">
                    <input type="hidden" name="vinculo_id" value="<?php echo (int) $item['vinculo_id']; ?>">
                    <input type="hidden" name="trilha_id" value="<?php echo (int) $trilha['id']; ?>">
                    <button type="submit">Homologar</button>
                </form>
                <form method="post" action="<?php echo url('homologacao/rejeitar'); ?>" style="display:inline;" onsubmit="return confirm('Rejeitar esta inscrição?');">
                    <input type="hidden" name="vinculo_id" value="<?php echo (int) $item['vinculo_id']; ?>">
                    <input type="hidden" name="trilha_id" value="<?php echo (int) $trilha['id']; ?>">
                    <button type="submit" class="btn-secundario">Rejeitar</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
