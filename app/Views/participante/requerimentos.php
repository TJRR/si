<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Requerimentos</h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('participante/index'); ?>" class="btn-acao">Minha inscrição</a>
    </div>
</div>

<h2>Modelos disponíveis</h2>
<?php if (empty($modelosDisponiveis)): ?>
    <p>Nenhum modelo de documento disponível para sua equipe no momento.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Modelo</th><th>Finalidade</th><th>Ação</th></tr>
        <?php foreach ($modelosDisponiveis as $modelo): ?>
        <tr>
            <td><?php echo htmlspecialchars($modelo['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($modelo['finalidade'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <a href="<?php echo url('requerimento/novo/' . (int) $modelo['id']); ?>" class="btn-icone" title="Iniciar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<h2 style="margin-top:1.5rem;">Meus pedidos</h2>
<?php
    $rotulosStatus = [
        'aguardando_assinatura' => 'Aguardando documento assinado',
        'recebido' => 'Recebido',
        'escalado' => 'Em análise',
        'esclarecimento_solicitado' => 'Esclarecimento solicitado',
        'aprovado' => 'Aprovado',
        'recusado' => 'Recusado',
        'revogado' => 'Revogado',
    ];
    $coresStatus = [
        'aguardando_assinatura' => 'cinza',
        'recebido' => 'azul',
        'escalado' => 'laranja',
        'esclarecimento_solicitado' => 'roxo',
        'aprovado' => 'verde',
        'recusado' => 'vermelho',
        'revogado' => 'vermelho',
    ];
?>
<?php if (empty($requerimentos)): ?>
    <p>Nenhum requerimento protocolado ainda.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Modelo</th><th>Status</th><th>Protocolado em</th><th>Ações</th></tr>
        <?php foreach ($requerimentos as $requerimento): ?>
        <tr>
            <td><?php echo htmlspecialchars($requerimento['modelo_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><span class="status-pill <?php echo $coresStatus[$requerimento['status']]; ?>"><?php echo $rotulosStatus[$requerimento['status']]; ?></span></td>
            <td><?php echo htmlspecialchars(formatarDataHora($requerimento['criado_em']), ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <a href="<?php echo url('requerimento/ver/' . (int) $requerimento['id']); ?>" class="btn-icone" title="Ver">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
