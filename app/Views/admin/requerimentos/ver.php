<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Requerimento — <?php echo htmlspecialchars($requerimento['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('home/administrativo'); ?>" class="btn-voltar">Voltar ao Painel</a></p>

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
    <p>
        <span class="status-pill <?php echo $coresStatus[$requerimento['status']]; ?>"><?php echo $rotulosStatus[$requerimento['status']]; ?></span>
        <?php if (!empty($atrasado)): ?>
            <span class="status-pill vermelho">Atrasado</span>
        <?php elseif (in_array($requerimento['status'], ['recebido', 'escalado'], true)): ?>
            <span class="status-pill verde">Em dia</span>
        <?php endif; ?>
    </p>
    <p><strong>Modelo:</strong> <?php echo htmlspecialchars($requerimento['modelo_nome'], ENT_QUOTES, 'UTF-8'); ?></p>
    <p><strong>Equipe:</strong> <?php echo htmlspecialchars($requerimento['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?></p>
    <p><strong>Líder:</strong> <?php echo htmlspecialchars($requerimento['participante_nome'], ENT_QUOTES, 'UTF-8'); ?></p>
    <p><strong>Trilha:</strong> <?php echo htmlspecialchars($requerimento['trilha_nome'], ENT_QUOTES, 'UTF-8'); ?></p>
    <p><strong>Necessidade:</strong><br><?php echo nl2br(htmlspecialchars($requerimento['necessidade'], ENT_QUOTES, 'UTF-8')); ?></p>
    <p><strong>Responsável atual:</strong> <?php echo $requerimento['responsavel_nome'] !== null ? htmlspecialchars($requerimento['responsavel_nome'], ENT_QUOTES, 'UTF-8') : 'Fila geral'; ?></p>
    <?php if ($requerimento['pdf_assinado_path'] !== null): ?>
        <p>
            <?php if ($requerimento['expurgado_em'] !== null): ?>
                Documento assinado expurgado em <?php echo htmlspecialchars(formatarData($requerimento['expurgado_em']), ENT_QUOTES, 'UTF-8'); ?>.
            <?php else: ?>
                <a href="<?php echo url('requerimentoAdmin/baixarAssinado/' . (int) $requerimento['id']); ?>" target="_blank">Baixar PDF assinado</a>
            <?php endif; ?>
        </p>
    <?php endif; ?>
</div>

<?php if (in_array($requerimento['status'], ['recebido', 'escalado'], true) || ($requerimento['status'] === 'aprovado' && $podeGerenciar)): ?>
<h2 style="margin-top:1.5rem;">Conferência da assinatura</h2>
<div class="admin-card">
    <?php if ($certificadoDeclarado !== null): ?>
        <div class="admin-card" style="background:#fff8e1;">
            <p><strong>Certificado declara:</strong>
                <?php echo htmlspecialchars($certificadoDeclarado['nome'], ENT_QUOTES, 'UTF-8'); ?><?php echo $certificadoDeclarado['cpf'] !== null ? ' — CPF ' . htmlspecialchars($certificadoDeclarado['cpf'], ENT_QUOTES, 'UTF-8') : ''; ?>
            </p>
            <p><small>Isto é o que o certificado declara, sem confirmação de validade — a confirmação real só acontece no site oficial do governo, abaixo.</small></p>
        </div>
    <?php endif; ?>
    <?php if (in_array($requerimento['status'], ['recebido', 'escalado'], true)): ?>
    <form method="post" action="<?php echo url('requerimentoAdmin/validarIti/' . (int) $requerimento['id']); ?>" style="margin-bottom:1rem;"><?= campoCsrf() ?>
        <button type="submit">Validar automaticamente no ITI</button>
        <p><small>Envia o PDF assinado direto pro validador oficial do governo, por uma janela de poucos minutos e uso único — um atalho de apoio, não substitui a conferência manual abaixo.</small></p>
    </form>
    <?php endif; ?>

    <p>Antes de aprovar, confirme a assinatura no validador oficial do governo:</p>
    <ol>
        <li>Baixe o PDF assinado (link acima, se ainda não baixou).</li>
        <li>Abra o <a href="https://validar.iti.gov.br" target="_blank" rel="noopener">validar.iti.gov.br</a> e use <strong>"Escolher Arquivo"</strong> (não "Colar URL" — o PDF fica fora do ar de propósito, por ter CPF de várias pessoas) para selecionar o mesmo arquivo. Deixe "Assinatura Destacada" desmarcado.</li>
        <li>Confira se o nome/CPF que o ITI mostrou bate com o líder da equipe (<?php echo htmlspecialchars($requerimento['participante_nome'], ENT_QUOTES, 'UTF-8'); ?>) e se o certificado está válido/não revogado.</li>
        <li>Depois de conferir, apague a cópia baixada do seu computador.</li>
    </ol>

    <?php if (in_array($requerimento['status'], ['recebido', 'escalado'], true)): ?>
    <h3>Responder</h3>
    <form method="post" action="<?php echo url('requerimentoAdmin/responder/' . (int) $requerimento['id']); ?>" enctype="multipart/form-data"><?= campoCsrf() ?>
        <label>Desfecho:
            <select name="desfecho" required>
                <?php if ($podeGerenciar): ?>
                    <option value="aprovado">Aprovado</option>
                    <option value="recusado">Recusado</option>
                <?php endif; ?>
                <option value="esclarecimento_solicitado">Pedir esclarecimentos</option>
            </select>
        </label><br>
        <label>Resposta:
            <textarea name="resposta" rows="5" required placeholder="Se aprovado: detalhes de como acessar. Se recusado: motivo. Se esclarecimento: o que falta esclarecer." style="width:100%; box-sizing:border-box;"></textarea>
        </label><br>
        <label>Anexo (opcional, PDF):
            <input type="file" name="anexo" accept="application/pdf">
        </label><br>
        <?php if ($podeGerenciar): ?>
        <label>
            <input type="checkbox" name="assinatura_conferida" value="1">
            Confirmo que validei a assinatura no site oficial do gov.br e ela pertence ao líder da equipe.
        </label><br>
        <p><small>Isto é obrigatório apenas para aprovar. A verificação automática desta tela (quando disponível) só mostra o que o próprio certificado declara — não substitui a checagem no validador oficial acima.</small></p>
        <?php endif; ?>
        <button type="submit">Registrar resposta</button>
    </form>
    <?php endif; ?>

    <?php if ($requerimento['status'] === 'aprovado' && $podeGerenciar): ?>
    <h3 style="margin-top:1rem;">Revogar acesso</h3>
    <form method="post" action="<?php echo url('requerimentoAdmin/responder/' . (int) $requerimento['id']); ?>" enctype="multipart/form-data" onsubmit="return confirm('Revogar o acesso já concedido a esta equipe? Esta ação não pode ser desfeita.');"><?= campoCsrf() ?>
        <input type="hidden" name="desfecho" value="revogado">
        <label>Motivo da revogação:
            <textarea name="resposta" rows="3" required style="width:100%; box-sizing:border-box;"></textarea>
        </label><br>
        <label>Anexo (opcional, PDF):
            <input type="file" name="anexo" accept="application/pdf">
        </label><br>
        <button type="submit">Revogar acesso</button>
    </form>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (in_array($requerimento['status'], ['recebido', 'escalado'], true)): ?>
<h2 style="margin-top:1.5rem;">Escalar</h2>
<div class="admin-card">
    <?php if (empty($atendentesDisponiveis)): ?>
        <p>Nenhum outro administrador, suporte ou colaborador disponível neste concurso.</p>
    <?php else: ?>
        <form method="post" action="<?php echo url('requerimentoAdmin/escalar/' . (int) $requerimento['id']); ?>"><?= campoCsrf() ?>
            <label>Escalar para:
                <select name="usuario_id" required>
                    <option value="">Selecione…</option>
                    <?php foreach ($atendentesDisponiveis as $atendente): ?>
                        <option value="<?php echo (int) $atendente['id']; ?>"><?php echo htmlspecialchars($atendente['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit">Escalar</button>
        </form>
    <?php endif; ?>
    <?php if ($requerimento['status'] === 'escalado' && $podeGerenciar): ?>
        <form method="post" action="<?php echo url('requerimentoAdmin/retomar/' . (int) $requerimento['id']); ?>" onsubmit="return confirm('Retomar este requerimento pra fila geral?');" style="margin-top:0.5rem;"><?= campoCsrf() ?>
            <button type="submit">Retomar pra fila geral</button>
        </form>
    <?php endif; ?>
</div>
<?php endif; ?>

<h2 style="margin-top:1.5rem;">Histórico de respostas</h2>
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
                        <a href="<?php echo url('requerimentoAdmin/baixarAnexoResposta/' . (int) $requerimento['id'] . '/' . (int) $resposta['id']); ?>" target="_blank">Baixar anexo</a>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<h2 style="margin-top:1.5rem;">Histórico de escalonamento</h2>
<?php if (empty($escalonamentos)): ?>
    <p>Nenhum escalonamento registrado.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>De</th><th>Para</th><th>Quando</th></tr>
        <?php foreach ($escalonamentos as $escalonamento): ?>
        <tr>
            <td><?php echo htmlspecialchars($escalonamento['de_usuario_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($escalonamento['para_usuario_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars(formatarDataHora($escalonamento['criado_em']), ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
