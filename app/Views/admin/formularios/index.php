<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Formulários de <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('trilhas/index/' . (int) $concurso['id']); ?>">Voltar às trilhas</a></p>
<p><a href="<?php echo url('formularios/novo/' . (int) $concurso['id']); ?>">+ Novo formulario</a></p>

<?php if (empty($formularios)): ?>
    <p>Nenhum formulário cadastrado.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Nome</th><th>Versão</th><th>Status</th><th>Ações</th></tr>
        <?php foreach ($formularios as $formulario): ?>
        <tr>
            <td><?php echo htmlspecialchars($formulario['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo (int) $formulario['versao']; ?></td>
            <td><?php echo htmlspecialchars($formulario['status'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <div class="acoes-icones">
                    <a href="<?php echo url('formularios/editar/' . (int) $formulario['id']); ?>" class="btn-icone" title="Editar">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </a>

                    <?php if (in_array($formulario['status'], ['rascunho', 'despublicado'], true)): ?>
                        <form method="post" action="<?php echo url('formularios/publicar'); ?>">
                            <input type="hidden" name="id" value="<?php echo (int) $formulario['id']; ?>">
                            <button type="submit" class="btn-icone" title="Publicar">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($formulario['status'] === 'publicado'): ?>
                        <form method="post" action="<?php echo url('formularios/despublicar'); ?>">
                            <input type="hidden" name="id" value="<?php echo (int) $formulario['id']; ?>">
                            <button type="submit" class="btn-icone" title="Despublicar">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($formulario['status'] === 'despublicado'): ?>
                        <form method="post" action="<?php echo url('formularios/arquivar'); ?>">
                            <input type="hidden" name="id" value="<?php echo (int) $formulario['id']; ?>">
                            <button type="submit" class="btn-icone" title="Arquivar">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <polyline points="21 8 21 21 3 21 3 8"></polyline>
                                    <rect x="1" y="3" width="22" height="5"></rect>
                                    <line x1="10" y1="12" x2="14" y2="12"></line>
                                </svg>
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($formulario['status'] === 'arquivado'): ?>
                        <form method="post" action="<?php echo url('formularios/desarquivar'); ?>">
                            <input type="hidden" name="id" value="<?php echo (int) $formulario['id']; ?>">
                            <button type="submit" class="btn-icone" title="Desarquivar">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <polyline points="21 8 21 21 3 21 3 8"></polyline>
                                    <rect x="1" y="3" width="22" height="5"></rect>
                                    <line x1="10" y1="12" x2="14" y2="12"></line>
                                </svg>
                            </button>
                        </form>
                    <?php endif; ?>

                    <a href="<?php echo url('campos/index/' . (int) $formulario['id']); ?>" class="btn-icone" title="Campos">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </a>

                    <?php if (in_array($formulario['status'], ['publicado', 'despublicado'], true)): ?>
                        <a href="<?php echo url('formularios/duplicarConfirmar/' . (int) $formulario['id']); ?>" class="btn-icone" title="Duplicar">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                        </a>
                    <?php endif; ?>

                    <form method="post" action="<?php echo url('formularios/remover'); ?>" onsubmit="return confirm('Remover este formulário? Só funciona se ele ainda não tiver campos, etapas vinculadas ou submissões.');">
                        <input type="hidden" name="id" value="<?php echo (int) $formulario['id']; ?>">
                        <input type="hidden" name="concurso_id" value="<?php echo (int) $concurso['id']; ?>">
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
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
