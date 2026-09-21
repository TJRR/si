<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>

<div class="site-page">
    <?php
    $eventoId = $evento['id'];
    $tituloTopo = $evento['nome'];
    require __DIR__ . '/_app_bar.php';
    ?>

    <div class="site-form-page">
        <?php require __DIR__ . '/_ajuda_card.php'; ?>
        <h2>Atividades</h2>

        <p>
            <a href="<?php echo url('eventoApp/presenca/' . (int) $evento['id']); ?>" class="btn">Confirmar presença</a>
        </p>

        <?php if (empty($atividades)): ?>
            <p>Nenhuma atividade cadastrada ainda.</p>
        <?php endif; ?>

        <?php foreach ($atividades as $atividade): ?>
        <?php
        $meuStatus = isset($statusPorAtividade[(int) $atividade['id']]) ? $statusPorAtividade[(int) $atividade['id']] : null;
        $lotada = $atividade['vagas'] !== null && (int) $atividade['total_confirmadas'] >= (int) $atividade['vagas'];
        ?>
        <div class="admin-card">
            <p><strong><?php echo htmlspecialchars($atividade['nome'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
            <p>
                <?php echo htmlspecialchars(formatarDataHora($atividade['data_inicio']), ENT_QUOTES, 'UTF-8'); ?>
                a
                <?php echo htmlspecialchars(formatarDataHora($atividade['data_fim']), ENT_QUOTES, 'UTF-8'); ?>
                <?php if (!empty($atividade['local'])): ?>
                    , em <?php echo htmlspecialchars($atividade['local'], ENT_QUOTES, 'UTF-8'); ?>
                <?php endif; ?>
            </p>
            <?php if (!empty($atividade['descricao_html'])): ?>
                <div><?php echo $atividade['descricao_html']; ?></div>
            <?php endif; ?>

            <?php if (empty($atividade['exige_inscricao'])): ?>
                <span class="status-pill">Não exige inscrição</span>
            <?php elseif ($meuStatus === 'confirmada'): ?>
                <span class="status-pill verde">Inscrição confirmada</span>
                <form method="post" action="<?php echo url('eventoApp/cancelarInscricaoAtividade'); ?>"><?= campoCsrf() ?>
                    <input type="hidden" name="atividade_id" value="<?php echo (int) $atividade['id']; ?>">
                    <button type="submit" class="btn">Cancelar inscrição</button>
                </form>
            <?php elseif ($meuStatus === 'espera'): ?>
                <span class="status-pill laranja">Na lista de espera</span>
                <form method="post" action="<?php echo url('eventoApp/cancelarInscricaoAtividade'); ?>"><?= campoCsrf() ?>
                    <input type="hidden" name="atividade_id" value="<?php echo (int) $atividade['id']; ?>">
                    <button type="submit" class="btn">Cancelar</button>
                </form>
            <?php elseif (!$lotada): ?>
                <form method="post" action="<?php echo url('eventoApp/inscreverAtividade'); ?>"><?= campoCsrf() ?>
                    <input type="hidden" name="atividade_id" value="<?php echo (int) $atividade['id']; ?>">
                    <button type="submit" class="btn">Inscrever-se</button>
                </form>
            <?php elseif (!empty($atividade['permite_lista_espera'])): ?>
                <form method="post" action="<?php echo url('eventoApp/inscreverAtividade'); ?>"><?= campoCsrf() ?>
                    <input type="hidden" name="atividade_id" value="<?php echo (int) $atividade['id']; ?>">
                    <button type="submit" class="btn">Atividade lotada: entrar na lista de espera</button>
                </form>
            <?php else: ?>
                <span class="status-pill vermelho">Lotada</span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
