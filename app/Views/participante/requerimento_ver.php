<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Requerimento — <?php echo htmlspecialchars($requerimento['modelo_nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('requerimento/index'); ?>" class="btn-voltar">Voltar</a></p>

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

<div class="admin-card">
    <p><span class="status-pill <?php echo $coresStatus[$requerimento['status']]; ?>"><?php echo $rotulosStatus[$requerimento['status']]; ?></span></p>
    <p><strong>Necessidade:</strong><br><?php echo nl2br(htmlspecialchars($requerimento['necessidade'], ENT_QUOTES, 'UTF-8')); ?></p>
    <p><strong>Protocolado em:</strong> <?php echo htmlspecialchars(formatarDataHora($requerimento['criado_em']), ENT_QUOTES, 'UTF-8'); ?></p>
    <?php if ($requerimento['pdf_assinado_path'] !== null): ?>
        <p>
            <?php if ($requerimento['expurgado_em'] !== null): ?>
                Documento assinado expurgado em <?php echo htmlspecialchars(formatarData($requerimento['expurgado_em']), ENT_QUOTES, 'UTF-8'); ?>, conforme a política de retenção de dados.
            <?php else: ?>
                <a href="<?php echo url('requerimento/baixarAssinado/' . (int) $requerimento['id']); ?>" target="_blank">Baixar documento assinado enviado</a>
            <?php endif; ?>
        </p>
    <?php endif; ?>
</div>

<?php if ($requerimento['status'] === 'aguardando_assinatura'): ?>
    <h2 style="margin-top:1.5rem;">
        <?php echo $requerimento['pdf_assinado_path'] === null ? 'Gerar e assinar o documento' : 'Gerar novo documento (correção pedida)'; ?>
    </h2>
    <div class="admin-card">
        <form method="post" action="<?php echo url('requerimento/gerarPdfNovamente/' . (int) $requerimento['id']); ?>">
            <label>Necessidade (descrever brevemente):
                <textarea name="necessidade" rows="5" required><?php echo htmlspecialchars($requerimento['necessidade'], ENT_QUOTES, 'UTF-8'); ?></textarea>
            </label><br>
            <button type="submit">Gerar PDF</button>
        </form>

        <p style="margin-top:1rem;">Depois de assinar no gov.br, envie o documento assinado abaixo.</p>
        <form method="post" action="<?php echo url('requerimento/enviarAssinado/' . (int) $requerimento['id']); ?>" enctype="multipart/form-data">
            <label>Documento assinado (PDF, até <?php echo htmlspecialchars($limiteMB, ENT_QUOTES, 'UTF-8'); ?>MB):
                <input type="file" name="pdf_assinado" accept="application/pdf" required>
            </label><br>
            <button type="submit">Enviar</button>
        </form>

        <?php if ($requerimento['enviado_em'] === null): ?>
            <form method="post" action="<?php echo url('requerimento/descartar/' . (int) $requerimento['id']); ?>" onsubmit="return confirm('Descartar este rascunho? Esta ação não pode ser desfeita.');" style="margin-top:1rem;">
                <button type="submit" class="btn-voltar">Descartar rascunho</button>
            </form>
        <?php endif; ?>
    </div>
<?php endif; ?>

<h2 style="margin-top:1.5rem;">Histórico</h2>
<?php if (empty($respostas)): ?>
    <p>Nenhuma resposta registrada ainda.</p>
<?php else: ?>
    <?php foreach ($respostas as $resposta): ?>
        <div class="admin-card">
            <p><strong><?php echo htmlspecialchars($rotulosStatus[$resposta['desfecho']], ENT_QUOTES, 'UTF-8'); ?></strong>
                — <?php echo htmlspecialchars($resposta['usuario_nome'], ENT_QUOTES, 'UTF-8'); ?>,
                <?php echo htmlspecialchars(formatarDataHora($resposta['criado_em']), ENT_QUOTES, 'UTF-8'); ?></p>
            <p><?php echo nl2br(htmlspecialchars($resposta['resposta'], ENT_QUOTES, 'UTF-8')); ?></p>
            <?php if ($resposta['anexo_path'] !== null): ?>
                <p>
                    <?php if ($resposta['anexo_expurgado_em'] !== null): ?>
                        Anexo expurgado em <?php echo htmlspecialchars(formatarData($resposta['anexo_expurgado_em']), ENT_QUOTES, 'UTF-8'); ?>.
                    <?php else: ?>
                        <a href="<?php echo url('requerimento/baixarAnexoResposta/' . (int) $requerimento['id'] . '/' . (int) $resposta['id']); ?>" target="_blank">Baixar anexo</a>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
