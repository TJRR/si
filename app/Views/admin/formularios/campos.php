<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Campos de <?php echo htmlspecialchars($formulario['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('formularios/index/' . (int) $formulario['concurso_id']); ?>">Voltar aos formulários</a></p>

<p>Status do formulário: <strong><?php echo htmlspecialchars($formulario['status'], ENT_QUOTES, 'UTF-8'); ?></strong></p>

<?php $editavel = $formulario['status'] === 'rascunho'; ?>

<?php if (!$editavel): ?>
    <p style="color:#b06000;">Este formulário já foi publicado. Para alterar os campos, duplique-o (tela de Formulários) e edite a nova versão.</p>
<?php else: ?>
    <p><a href="<?php echo url('campos/novo/' . (int) $formulario['id']); ?>">+ Novo campo</a></p>
<?php endif; ?>

<?php if (empty($campos)): ?>
    <p>Nenhum campo cadastrado.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Ordem</th><th>Rótulo</th><th>Tipo</th><th>Obrigatório</th><th>Ações</th></tr>
        <?php foreach ($campos as $campo): ?>
        <tr>
            <td><?php echo (int) $campo['ordem']; ?></td>
            <td><?php echo htmlspecialchars($campo['rotulo'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($campo['tipo'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo $campo['obrigatorio'] ? 'Sim' : 'Não'; ?></td>
            <td>
                <?php if ($editavel): ?>
                    <div class="acoes-icones">
                        <a href="<?php echo url('campos/editar/' . (int) $campo['id']); ?>" class="btn-icone" title="Editar">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </a>
                        <form method="post" action="<?php echo url('campos/mover'); ?>">
                            <input type="hidden" name="id" value="<?php echo (int) $campo['id']; ?>">
                            <input type="hidden" name="formulario_id" value="<?php echo (int) $formulario['id']; ?>">
                            <input type="hidden" name="direcao" value="cima">
                            <button type="submit" class="btn-icone" title="Mover para cima">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <line x1="12" y1="19" x2="12" y2="5"></line>
                                    <polyline points="5 12 12 5 19 12"></polyline>
                                </svg>
                            </button>
                        </form>
                        <form method="post" action="<?php echo url('campos/mover'); ?>">
                            <input type="hidden" name="id" value="<?php echo (int) $campo['id']; ?>">
                            <input type="hidden" name="formulario_id" value="<?php echo (int) $formulario['id']; ?>">
                            <input type="hidden" name="direcao" value="baixo">
                            <button type="submit" class="btn-icone" title="Mover para baixo">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <polyline points="19 12 12 19 5 12"></polyline>
                                </svg>
                            </button>
                        </form>
                        <form method="post" action="<?php echo url('campos/remover'); ?>">
                            <input type="hidden" name="id" value="<?php echo (int) $campo['id']; ?>">
                            <input type="hidden" name="formulario_id" value="<?php echo (int) $formulario['id']; ?>">
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
                    </div>
                <?php else: ?>
                    —
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
