<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Progresso da avaliação — <?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('designacoes/index/' . (int) $etapa['id']); ?>">Voltar à designação de avaliadores</a></p>

<?php if (empty($progresso)): ?>
    <p>Nenhum avaliador com submissões para avaliar nesta etapa ainda.</p>
<?php else: ?>
    <div class="tabela-scroll">
        <table>
            <tr><th>Avaliador</th><th>Concluídas</th><th>Pendentes</th><th>Progresso</th><th>Submissões pendentes</th></tr>
            <?php foreach ($progresso as $linha): ?>
            <?php $percentual = $linha['total'] > 0 ? (int) round($linha['completas'] / $linha['total'] * 100) : 0; ?>
            <tr>
                <td><?php echo htmlspecialchars($linha['usuario_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo (int) $linha['completas']; ?> de <?php echo (int) $linha['total']; ?></td>
                <td>
                    <?php if (empty($linha['pendentes'])): ?>
                        <span class="status-pill verde">Nenhuma</span>
                    <?php else: ?>
                        <span class="status-pill laranja"><?php echo count($linha['pendentes']); ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="admin-progresso-barra-fundo" style="max-width: 160px;">
                        <div class="admin-progresso-barra-preenchimento" style="width: <?php echo $percentual; ?>%;"></div>
                    </div>
                </td>
                <td>
                    <?php if (empty($linha['pendentes'])): ?>
                        —
                    <?php else: ?>
                        <?php foreach ($linha['pendentes'] as $pendente): ?>
                            #<?php echo (int) $pendente['submissao_id']; ?>
                            (<?php echo htmlspecialchars($pendente['nome_equipe'] !== null ? $pendente['nome_equipe'] : 'sem equipe', ENT_QUOTES, 'UTF-8'); ?>)<br>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>
