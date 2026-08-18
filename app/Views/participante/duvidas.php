<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Dúvidas</h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('duvida/novo'); ?>" class="btn-acao">Registrar dúvida</a>
        <a href="<?php echo url('participante/index'); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<?php if (!empty($_SESSION['flash'])): ?>
    <p class="flash-mensagem <?php echo classeFlash(); ?>"><?php echo htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['flash']); ?></p>
<?php endif; ?>

<?php
    $rotulosStatus = ['recebida' => 'Recebida', 'escalada' => 'Em análise', 'respondida' => 'Respondida'];
    $coresStatus = ['recebida' => 'azul', 'escalada' => 'laranja', 'respondida' => 'verde'];
?>

<?php if (empty($duvidas)): ?>
    <p>Sua equipe ainda não registrou nenhuma dúvida.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Pergunta</th><th>Registrada por</th><th>Data</th><th>Status</th><th>Ação</th></tr>
        <?php foreach ($duvidas as $duvida): ?>
        <tr>
            <td><?php echo htmlspecialchars(mb_substr($duvida['pergunta'], 0, 80) . (mb_strlen($duvida['pergunta']) > 80 ? '…' : ''), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($duvida['participante_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars(formatarDataHora($duvida['criado_em']), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><span class="status-pill <?php echo $coresStatus[$duvida['status']]; ?>"><?php echo $rotulosStatus[$duvida['status']]; ?></span></td>
            <td>
                <div class="acoes-icones">
                    <a href="<?php echo url('duvida/ver/' . (int) $duvida['id']); ?>" class="btn-icone" title="Ver">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
