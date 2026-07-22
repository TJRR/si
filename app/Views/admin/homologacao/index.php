<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Inscritos — <?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if (!empty($flash)): ?>
    <p style="color:green;"><?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php if (\App\Core\Auth::possuiPerfil('administrador')): ?>
    <p>
        <?php if ($homologacaoPublicada): ?>
            <strong>Página pública de equipes homologadas: publicada.</strong>
            <form method="post" action="<?php echo url('homologacao/despublicar/' . (int) $trilha['id']); ?>" style="display:inline;">
                <button type="submit" class="btn-icone" title="Despublicar" onclick="return confirm('A página pública de equipes homologadas desta trilha vai sair do ar. Confirmar?');">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="1 4 1 10 7 10"></polyline>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                    </svg>
                </button>
            </form>
        <?php else: ?>
            <strong>Página pública de equipes homologadas: não publicada.</strong>
            <form method="post" action="<?php echo url('homologacao/publicar/' . (int) $trilha['id']); ?>" style="display:inline;">
                <button type="submit" class="btn-icone" title="Publicar" onclick="return confirm('Publica a lista de equipes homologadas desta trilha numa página pública. Confirmar?');">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </button>
            </form>
        <?php endif; ?>
    </p>
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
    <!--
        form-acoes-em-massa fica vazio, so' com trilha_id, e os controles que
        pertencem a ele (checkboxes da tabela + botoes em massa no fim da
        pagina) se associam via atributo form="form-acoes-em-massa" em vez de
        aninhamento de <form> - HTML nao permite <form> dentro de <form>
        (era o caso antes, com a tabela inteira dentro deste form: o
        navegador descarta o form aninhado, entao os botoes "Homologar"/
        "Rejeitar" de cada linha acabavam submetendo ESTE form em vez do
        deles, sem action nenhuma - por isso o botao "nao funcionava").
    -->
    <form method="post" id="form-acoes-em-massa">
        <input type="hidden" name="trilha_id" value="<?php echo (int) $trilha['id']; ?>">
    </form>

    <table border="1" cellpadding="6">
            <tr>
                <th><input type="checkbox" onclick="document.querySelectorAll('.marcar-linha').forEach(function(c){c.checked=this.checked;}, this)"></th>
                <th>Equipe</th><th>Participante</th><th>Papel</th><th>CPF</th><th>E-mail</th><th>Telefone</th><th>Status</th><th>Ações</th>
            </tr>
            <?php foreach ($inscricoes as $item): ?>
            <tr>
                <td><input type="checkbox" class="marcar-linha" name="vinculo_ids[]" value="<?php echo (int) $item['vinculo_id']; ?>" form="form-acoes-em-massa"></td>
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
                    <div class="acoes-icones">
                        <?php if ($item['status_homologacao'] !== 'homologado'): ?>
                            <form method="post" action="<?php echo url('homologacao/homologar'); ?>">
                                <input type="hidden" name="vinculo_id" value="<?php echo (int) $item['vinculo_id']; ?>">
                                <input type="hidden" name="trilha_id" value="<?php echo (int) $trilha['id']; ?>">
                                <button type="submit" class="btn-icone" title="Homologar">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                </button>
                            </form>
                        <?php endif; ?>
                        <?php if ($item['status_homologacao'] !== 'rejeitado'): ?>
                            <form method="post" action="<?php echo url('homologacao/rejeitar'); ?>" onsubmit="return confirm('Rejeitar esta inscrição?');">
                                <input type="hidden" name="vinculo_id" value="<?php echo (int) $item['vinculo_id']; ?>">
                                <input type="hidden" name="trilha_id" value="<?php echo (int) $trilha['id']; ?>">
                                <button type="submit" class="btn-icone" title="Rejeitar">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="15" y1="9" x2="9" y2="15"></line>
                                        <line x1="9" y1="9" x2="15" y2="15"></line>
                                    </svg>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
    </table>

    <p>Com os selecionados:
        <span class="acoes-icones" style="display:inline-flex;">
            <button type="submit" form="form-acoes-em-massa" formaction="<?php echo url('homologacao/homologarEmMassa'); ?>" class="btn-icone" title="Homologar selecionados">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </button>
            <button type="submit" form="form-acoes-em-massa" formaction="<?php echo url('homologacao/rejeitarEmMassa'); ?>" class="btn-icone" title="Rejeitar selecionados" onclick="return confirm('Rejeitar todas as inscrições selecionadas?');">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
            </button>
        </span>
    </p>
<?php endif; ?>
