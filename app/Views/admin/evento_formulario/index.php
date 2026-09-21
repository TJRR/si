<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Formulário de inscrição: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <?php if (\App\Core\Auth::possuiPerfil('administrador')): ?>
        <a href="<?php echo url('eventoFormulario/novo/' . (int) $evento['id']); ?>" class="btn-acao">+ Novo campo</a>
        <?php endif; ?>
    </div>
</div>

<p style="color:#555;">
    O campo <strong>Documento</strong> é estrutural e sempre aparece primeiro na inscrição: não faz parte desta lista. Os campos abaixo aparecem depois dele, na ordem definida aqui.
</p>

<?php if (empty($campos)): ?>
    <p>Nenhum campo configurado ainda.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Ordem</th><th>Rótulo</th><th>Tipo</th><th>Obrigatório</th><th>Ações</th></tr>
        <?php foreach ($campos as $indice => $campo): ?>
        <tr>
            <td><?php echo (int) $campo['ordem']; ?></td>
            <td><?php echo htmlspecialchars($campo['rotulo'], ENT_QUOTES, 'UTF-8'); ?></td>
            <?php $rotuloTipo = isset(\App\Controllers\EventoFormularioAdminController::TIPOS[$campo['tipo']]) ? \App\Controllers\EventoFormularioAdminController::TIPOS[$campo['tipo']] : $campo['tipo']; ?>
            <td><?php echo htmlspecialchars($rotuloTipo, ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo $campo['obrigatorio'] ? 'Sim' : 'Não'; ?></td>
            <td>
                <?php if (\App\Core\Auth::possuiPerfil('administrador')): ?>
                <div class="acoes-icones">
                    <?php if ($indice > 0): ?>
                    <form method="post" action="<?php echo url('eventoFormulario/mover'); ?>" style="display:inline;"><?= campoCsrf() ?>
                        <input type="hidden" name="id" value="<?php echo (int) $campo['id']; ?>">
                        <input type="hidden" name="evento_id" value="<?php echo (int) $evento['id']; ?>">
                        <input type="hidden" name="direcao" value="cima">
                        <button type="submit" class="btn-icone" title="Mover para cima">▲</button>
                    </form>
                    <?php endif; ?>
                    <?php if ($indice < count($campos) - 1): ?>
                    <form method="post" action="<?php echo url('eventoFormulario/mover'); ?>" style="display:inline;"><?= campoCsrf() ?>
                        <input type="hidden" name="id" value="<?php echo (int) $campo['id']; ?>">
                        <input type="hidden" name="evento_id" value="<?php echo (int) $evento['id']; ?>">
                        <input type="hidden" name="direcao" value="baixo">
                        <button type="submit" class="btn-icone" title="Mover para baixo">▼</button>
                    </form>
                    <?php endif; ?>
                    <a href="<?php echo url('eventoFormulario/editar/' . (int) $campo['id']); ?>" class="btn-icone" title="Editar">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </a>
                    <form method="post" action="<?php echo url('eventoFormulario/remover'); ?>" style="display:inline;" onsubmit="return confirm('Remover este campo do formulário de inscrição?');"><?= campoCsrf() ?>
                        <input type="hidden" name="id" value="<?php echo (int) $campo['id']; ?>">
                        <input type="hidden" name="evento_id" value="<?php echo (int) $evento['id']; ?>">
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
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
