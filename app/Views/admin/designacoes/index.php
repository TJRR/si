<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Designação de avaliadores — <?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('etapas/index/' . (int) $trilha['id']); ?>">Voltar às etapas</a></p>

<?php if (!empty($flash)): ?>
    <p style="color:green;"><?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<p>Modo de designação desta etapa: <strong><?php
    echo [
        'manual' => 'Admin atribui manualmente',
        'aberto' => 'Todo avaliador da trilha vê tudo (não precisa designar)',
        'automatico' => 'Distribuição automática balanceada',
        'sorteio_categoria' => 'Sorteio aleatório garantindo 1 avaliador de cada categoria',
    ][$etapa['modo_designacao']] ?? 'Não definido — edite a etapa e configure antes de designar';
?></strong></p>

<?php if ($etapa['modo_designacao'] === 'sorteio_categoria'): ?>
    <p><a href="<?php echo url('vagasAvaliador/index/' . (int) $etapa['id']); ?>">Configurar vagas por categoria</a></p>
<?php endif; ?>

<?php if ($etapa['modo_designacao'] === 'aberto'): ?>
    <p>Como o modo é "aberto", qualquer avaliador vinculado a este concurso já pode notar todas as submissões desta etapa — não há necessidade de designar manualmente.</p>
<?php else: ?>

    <?php if ($etapa['modo_designacao'] === 'automatico' || $etapa['modo_designacao'] === 'sorteio_categoria'): ?>
        <p><a href="<?php echo url('designacoes/distribuir/' . (int) $etapa['id']); ?>">Distribuir automaticamente (ver prévia antes de confirmar)</a></p>
    <?php endif; ?>

    <?php if (empty($avaliadores)): ?>
        <p><strong>Nenhum avaliador vinculado a este concurso ainda.</strong> Aprove um cadastro com perfil "Avaliador" em Usuários antes de designar.</p>
    <?php else: ?>

    <form method="get" action="<?php echo config('base_path'); ?>/index.php">
        <input type="hidden" name="r" value="designacoes/index/<?php echo (int) $etapa['id']; ?>">
        <label>Filtrar por avaliador:
            <select name="filtro_avaliador">
                <option value="">Todos</option>
                <option value="sem_avaliador" <?php echo $filtroAvaliador === 'sem_avaliador' ? 'selected' : ''; ?>>Sem avaliador designado</option>
                <?php foreach ($avaliadores as $avaliador): ?>
                    <option value="<?php echo (int) $avaliador['id']; ?>" <?php echo (string) $filtroAvaliador === (string) $avaliador['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($avaliador['nome'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Status de nota:
            <select name="filtro_nota">
                <option value="">Todos</option>
                <option value="lancada" <?php echo $filtroNota === 'lancada' ? 'selected' : ''; ?>>Nota já lançada</option>
                <option value="pendente" <?php echo $filtroNota === 'pendente' ? 'selected' : ''; ?>>Nota pendente</option>
            </select>
        </label>
        <button type="submit">Filtrar</button>
    </form>

    <form method="post" action="<?php echo url('designacoes/atribuirEmMassa'); ?>">
        <input type="hidden" name="etapa_id" value="<?php echo (int) $etapa['id']; ?>">
        <p>
            <input type="checkbox" id="marcar-todos">
            <label for="marcar-todos">Selecionar todos</label>
            —
            <select name="usuario_id">
                <option value="">Escolha o avaliador...</option>
                <?php foreach ($avaliadores as $avaliador): ?>
                    <option value="<?php echo (int) $avaliador['id']; ?>">
                        <?php echo htmlspecialchars($avaliador['nome'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Atribuir aos selecionados</button>
        </p>

        <table border="1" cellpadding="6">
            <tr><th></th><th>Submissão</th><th>Equipe</th><th>Avaliadores designados</th><th>Nota</th></tr>
            <?php foreach ($submissoes as $submissao): ?>
            <tr>
                <td><input type="checkbox" name="submissao_ids[]" value="<?php echo (int) $submissao['id']; ?>" class="marcar-linha"></td>
                <td>#<?php echo (int) $submissao['id']; ?></td>
                <td><?php echo htmlspecialchars($submissao['nome_equipe'] !== null ? $submissao['nome_equipe'] : '—', ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <?php if (empty($submissao['designacoes'])): ?>
                        Nenhum
                    <?php else: ?>
                        <?php foreach ($submissao['designacoes'] as $designacao): ?>
                            <?php echo htmlspecialchars($designacao['usuario_nome'], ENT_QUOTES, 'UTF-8'); ?>
                            <?php if (!empty($designacao['categoria_nome'])): ?>
                                <em>(<?php echo htmlspecialchars($designacao['categoria_nome'], ENT_QUOTES, 'UTF-8'); ?>)</em>
                            <?php endif; ?>
                            <?php if ($designacao['travada']): ?>
                                <span title="<?php echo $designacao['origem'] === 'sorteio' ? 'Designação de sorteio — não pode ser removida' : 'Avaliador já lançou nota — não pode ser removida'; ?>">🔒</span>
                            <?php else: ?>
                                <button type="submit" form="remover-<?php echo (int) $designacao['id']; ?>" class="btn-icone" title="Remover designação">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                                        <path d="M10 11v6"></path>
                                        <path d="M14 11v6"></path>
                                        <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                                    </svg>
                                </button>
                            <?php endif; ?><br>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </td>
                <td><?php echo $submissao['tem_nota_lancada'] ? 'Lançada' : 'Pendente'; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </form>

    <?php foreach ($submissoes as $submissao): ?>
        <?php foreach ($submissao['designacoes'] as $designacao): ?>
            <form id="remover-<?php echo (int) $designacao['id']; ?>" method="post" action="<?php echo url('designacoes/remover'); ?>">
                <input type="hidden" name="id" value="<?php echo (int) $designacao['id']; ?>">
                <input type="hidden" name="etapa_id" value="<?php echo (int) $etapa['id']; ?>">
            </form>
        <?php endforeach; ?>
    <?php endforeach; ?>

    <?php endif; ?>
<?php endif; ?>
