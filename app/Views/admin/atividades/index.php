<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Atividades: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <?php if (\App\Core\Auth::possuiPerfil('administrador')): ?>
        <a href="<?php echo url('atividades/novo/' . (int) $evento['id']); ?>" class="btn-acao">+ Nova atividade</a>
        <?php endif; ?>
        <a href="<?php echo url('eventos/editar/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<?php if (empty($atividades)): ?>
    <p>Nenhuma atividade cadastrada.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Nome</th><th>Local</th><th>Período</th><th>Vagas</th><th>Ações</th></tr>
        <?php foreach ($atividades as $atividade): ?>
        <?php
        $ocupacao = $atividade['vagas'] !== null
            ? $atividade['total_confirmadas'] . ' / ' . $atividade['vagas']
            : ($atividade['exige_inscricao'] ? 'Ilimitada' : 'Não informado');
        ?>
        <tr>
            <td><?php echo htmlspecialchars($atividade['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string) $atividade['local'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <?php echo htmlspecialchars(formatarDataHora($atividade['data_inicio']), ENT_QUOTES, 'UTF-8'); ?>
                a
                <?php echo htmlspecialchars(formatarDataHora($atividade['data_fim']), ENT_QUOTES, 'UTF-8'); ?>
            </td>
            <td><?php echo htmlspecialchars($ocupacao, ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <div class="acoes-icones">
                    <a href="<?php echo url('atividades/inscritos/' . (int) $atividade['id']); ?>" class="btn-icone" title="Inscritos">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </a>
                    <?php if (\App\Core\Auth::possuiPerfil('administrador')): ?>
                    <a href="<?php echo url('atividades/editar/' . (int) $atividade['id']); ?>" class="btn-icone" title="Editar">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </a>
                    <form method="post" action="<?php echo url('atividades/remover'); ?>" onsubmit="return confirm('Remover esta atividade? Só funciona se ela ainda não tiver inscrições.');"><?= campoCsrf() ?>
                        <input type="hidden" name="id" value="<?php echo (int) $atividade['id']; ?>">
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
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
