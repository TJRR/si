<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Formulário vinculado — <?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if ($formulario === null): ?>
    <p>Esta etapa ainda não tem um formulário dinâmico vinculado.</p>
    <p><a href="<?php echo url('etapas/editar/' . (int) $etapa['id']); ?>">Vincular um formulário em Dados Gerais</a></p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Nome</th><td><?php echo htmlspecialchars($formulario['nome'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
        <tr><th>Versão</th><td><?php echo (int) $formulario['versao']; ?></td></tr>
        <tr><th>Status</th><td><?php echo htmlspecialchars($formulario['status'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
    </table>

    <div class="acoes-icones">
        <?php if ($formulario['status'] === 'publicado'): ?>
            <a href="<?php echo (int) $etapa['ordem'] === 1
                ? url('inscricao/formulario/' . (int) $etapa['id'])
                : url('submissao/preencher/' . (int) $etapa['id']); ?>" target="_blank" class="btn-icone" title="Ver formulário público">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                    <polyline points="15 3 21 3 21 9"></polyline>
                    <line x1="10" y1="14" x2="21" y2="3"></line>
                </svg>
            </a>
        <?php endif; ?>
        <?php if (\App\Core\Auth::possuiPerfil('administrador')): ?>
        <a href="<?php echo url('campos/index/' . (int) $formulario['id']); ?>" class="btn-icone" title="Campos">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
            </svg>
        </a>
        <a href="<?php echo url('formularios/editar/' . (int) $formulario['id']); ?>" class="btn-icone" title="Editar formulário">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
            </svg>
        </a>
        <?php if (in_array($formulario['status'], ['rascunho', 'despublicado'], true)): ?>
            <form method="post" action="<?php echo url('formularios/publicar'); ?>">
                <input type="hidden" name="id" value="<?php echo (int) $formulario['id']; ?>">
                <input type="hidden" name="etapa_id" value="<?php echo (int) $etapa['id']; ?>">
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
                <input type="hidden" name="etapa_id" value="<?php echo (int) $etapa['id']; ?>">
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
                <input type="hidden" name="etapa_id" value="<?php echo (int) $etapa['id']; ?>">
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
                <input type="hidden" name="etapa_id" value="<?php echo (int) $etapa['id']; ?>">
                <button type="submit" class="btn-icone" title="Desarquivar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="21 8 21 21 3 21 3 8"></polyline>
                        <rect x="1" y="3" width="22" height="5"></rect>
                        <line x1="10" y1="12" x2="14" y2="12"></line>
                    </svg>
                </button>
            </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>
