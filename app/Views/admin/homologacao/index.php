<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Inscritos — <?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if (!empty($flash)): ?>
    <p style="color:green;"><?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="get" action="<?php echo config('base_path'); ?>/index.php">
    <input type="hidden" name="r" value="homologacao/index/<?php echo (int) $trilha['id']; ?>">
    <label>Status:
        <select name="status" onchange="this.form.submit()">
            <option value="" <?php echo $statusFiltro === '' ? 'selected' : ''; ?>>Todos</option>
            <option value="pendente" <?php echo $statusFiltro === 'pendente' ? 'selected' : ''; ?>>Pendentes</option>
            <option value="homologado" <?php echo $statusFiltro === 'homologado' ? 'selected' : ''; ?>>Homologados</option>
            <option value="rejeitado" <?php echo $statusFiltro === 'rejeitado' ? 'selected' : ''; ?>>Rejeitados</option>
        </select>
    </label>
</form>

<?php if (empty($inscricoes)): ?>
    <p>Nenhuma inscrição encontrada<?php echo $statusFiltro !== '' ? ' com este filtro' : ' nesta trilha'; ?>.</p>
<?php else: ?>
    <form method="post" id="form-acoes-em-massa">
        <input type="hidden" name="trilha_id" value="<?php echo (int) $trilha['id']; ?>">
        <table border="1" cellpadding="6">
            <tr>
                <th><input type="checkbox" onclick="document.querySelectorAll('.marcar-linha').forEach(function(c){c.checked=this.checked;}, this)"></th>
                <th>Equipe</th><th>Participante</th><th>Papel</th><th>CPF</th><th>E-mail</th><th>Telefone</th><th>Status</th><th>Ações</th>
            </tr>
            <?php foreach ($inscricoes as $item): ?>
            <tr>
                <td><input type="checkbox" class="marcar-linha" name="vinculo_ids[]" value="<?php echo (int) $item['vinculo_id']; ?>"></td>
                <td><?php echo htmlspecialchars($item['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($item['participante_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo $item['papel'] === 'lider' ? 'Líder' : 'Integrante'; ?></td>
                <td><?php echo htmlspecialchars((string) $item['cpf'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars((string) $item['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars((string) $item['telefone'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <?php
                        $rotulosStatus = ['pendente' => 'Pendente', 'homologado' => 'Homologado', 'rejeitado' => 'Rejeitado'];
                        echo htmlspecialchars($rotulosStatus[$item['status_homologacao']], ENT_QUOTES, 'UTF-8');
                    ?>
                    <?php if ($item['status_homologacao'] === 'rejeitado' && !empty($item['motivo_rejeicao'])): ?>
                        <br><small><?php echo htmlspecialchars($item['motivo_rejeicao'], ENT_QUOTES, 'UTF-8'); ?></small>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($item['status_homologacao'] !== 'homologado'): ?>
                        <form method="post" action="<?php echo url('homologacao/homologar'); ?>" style="display:inline;">
                            <input type="hidden" name="vinculo_id" value="<?php echo (int) $item['vinculo_id']; ?>">
                            <input type="hidden" name="trilha_id" value="<?php echo (int) $trilha['id']; ?>">
                            <button type="submit">Homologar</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($item['status_homologacao'] !== 'rejeitado'): ?>
                        <form method="post" action="<?php echo url('homologacao/rejeitar'); ?>" style="display:inline;" onsubmit="return confirm('Rejeitar esta inscrição?');">
                            <input type="hidden" name="vinculo_id" value="<?php echo (int) $item['vinculo_id']; ?>">
                            <input type="hidden" name="trilha_id" value="<?php echo (int) $trilha['id']; ?>">
                            <button type="submit" class="btn-secundario">Rejeitar</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <p>Com os selecionados:
            <button type="submit" formaction="<?php echo url('homologacao/homologarEmMassa'); ?>">Homologar selecionados</button>
            <button type="submit" formaction="<?php echo url('homologacao/rejeitarEmMassa'); ?>" class="btn-secundario" onclick="return confirm('Rejeitar todas as inscrições selecionadas?');">Rejeitar selecionados</button>
        </p>
    </form>
<?php endif; ?>
