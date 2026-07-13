<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Etapas de <?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('trilhas/index/' . (int) $trilha['concurso_id']); ?>">Voltar às trilhas</a></p>
<?php if (\App\Core\Auth::possuiPerfil('administrador')): ?>
<p><a href="<?php echo url('etapas/novo/' . (int) $trilha['id']); ?>">+ Nova etapa</a></p>
<?php endif; ?>

<?php if (empty($etapas)): ?>
    <p>Nenhuma etapa cadastrada.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <?php $rotulosMecanismo = ['nenhuma' => 'Nenhuma', 'administrador' => 'Pelo Administrador', 'avaliadores' => 'Por Avaliadores']; ?>
        <tr><th>Nome</th><th>Ordem</th><th>Mecanismo</th><th>Período</th><th>Regra de transição</th><th>Formulário vinculado</th><th>Ações</th></tr>
        <?php foreach ($etapas as $etapa): ?>
        <tr>
            <td><?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo (int) $etapa['ordem']; ?></td>
            <td><?php echo htmlspecialchars($rotulosMecanismo[$etapa['mecanismo_avaliacao']], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <?php echo htmlspecialchars((string) $etapa['data_inicio'], ENT_QUOTES, 'UTF-8'); ?>
                a
                <?php echo htmlspecialchars((string) $etapa['data_fim'], ENT_QUOTES, 'UTF-8'); ?>
            </td>
            <td><?php echo $etapa['regra_transicao_tipo'] !== null ? htmlspecialchars($etapa['regra_transicao_tipo'] . ': ' . $etapa['regra_transicao_valor'], ENT_QUOTES, 'UTF-8') : '—'; ?></td>
            <td><?php echo $etapa['formulario_dinamico_id'] ? '#' . (int) $etapa['formulario_dinamico_id'] : '—'; ?></td>
            <td>
                <div class="acoes-icones">
                    <a href="<?php echo url('etapas/editar/' . (int) $etapa['id']); ?>" class="btn-icone" title="Editar">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </a>
                    <?php if (\App\Core\Auth::possuiPerfil('administrador')): ?>
                    <form method="post" action="<?php echo url('etapas/remover'); ?>" onsubmit="return confirm('Remover esta etapa? Só funciona se ela ainda não tiver critérios, notas ou submissões.');">
                        <input type="hidden" name="id" value="<?php echo (int) $etapa['id']; ?>">
                        <input type="hidden" name="trilha_id" value="<?php echo (int) $trilha['id']; ?>">
                        <button type="submit" class="btn-icone" title="Remover">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                                <path d="M10 11v6"></path>
                                <path d="M14 11v6"></path>
                                <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                            </svg>
                        </button>
                    </form>
                    <?php endif; ?>
                    <?php if ($etapa['formulario_dinamico_id'] && $etapa['formulario_status'] === 'publicado'): ?>
                        <?php $urlFormularioPublico = (int) $etapa['ordem'] === 1
                            ? url('inscricao/formulario/' . (int) $etapa['id'])
                            : url('submissao/preencher/' . (int) $etapa['id']); ?>
                        <a href="<?php echo $urlFormularioPublico; ?>" target="_blank" class="btn-icone" title="Ver formulário público">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                <polyline points="15 3 21 3 21 9"></polyline>
                                <line x1="10" y1="14" x2="21" y2="3"></line>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
