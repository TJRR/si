<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Acompanhamento dos bônus: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <?php if ($podeEditar): ?>
        <form method="post" action="<?php echo url('bonus/reconferir/' . (int) $evento['id']); ?>" style="display:inline;" onsubmit="return confirm('Reconferir os bônus de todos os inscritos agora?');"><?= campoCsrf() ?>
            <button type="submit" class="btn-acao">Reconferir agora</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="admin-card">
    <p>
        <strong><?php echo (int) $numeros['validos']; ?></strong> créditos válidos ·
        <strong><?php echo (int) $numeros['pontos']; ?></strong> pontos concedidos ·
        <strong><?php echo (int) $numeros['pessoas']; ?></strong> pessoas alcançadas ·
        <strong><?php echo (int) $numeros['anulados']; ?></strong> anulados
    </p>
</div>

<p style="color:#555;font-size:0.9em;">
    Anular um crédito aqui tira os pontos, avisa a pessoa e é definitivo: nenhuma presença nova o concede
    de novo, e o único caminho de volta é desfazer a anulação. Diferente disso é o crédito
    <strong>anulado pelo sistema</strong>, que aparece quando a organização remove uma presença e o bônus
    perde a quantidade exigida: esse volta sozinho se a presença for registrada outra vez.
</p>

<form method="get" action="<?php echo url('bonus/acompanhamento/' . (int) $evento['id']); ?>" style="margin: 1rem 0;">
    <label>Bônus:
        <select name="bonus_id" onchange="this.form.submit()">
            <option value="0">Todos</option>
            <?php foreach ($bonus as $item): ?>
                <option value="<?php echo (int) $item['id']; ?>" <?php echo (int) $filtros['bonus_id'] === (int) $item['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Situação:
        <select name="situacao" onchange="this.form.submit()">
            <option value="">Todas</option>
            <option value="validos" <?php echo $filtros['situacao'] === 'validos' ? 'selected' : ''; ?>>Válidos</option>
            <option value="anulados" <?php echo $filtros['situacao'] === 'anulados' ? 'selected' : ''; ?>>Anulados</option>
        </select>
    </label>
</form>

<?php if ($podeEditar && !empty($bonus)): ?>
<div class="admin-card">
    <p><strong>Cancelar todos os créditos de um bônus</strong></p>
    <p style="color:#555;font-size:0.9em;">
        Use quando um bônus foi cadastrado errado e concedeu pontos a quem não devia. Cada pessoa atingida
        recebe um aviso no aplicativo, então com muitos créditos a operação demora alguns segundos.
        Para confirmar, digite o nome exato do bônus.
    </p>
    <?php foreach ($bonus as $item): ?>
    <?php if ((int) $item['total_creditos'] === 0) { continue; } ?>
    <form method="post" action="<?php echo url('bonus/anularEmLote/' . (int) $evento['id'] . '/' . (int) $item['id']); ?>" onsubmit="return confirm('Cancelar todos os créditos válidos deste bônus?');"><?= campoCsrf() ?>
        <p>
            <strong><?php echo htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
            (<?php echo (int) $item['total_creditos']; ?> <?php echo (int) $item['total_creditos'] === 1 ? 'crédito válido' : 'créditos válidos'; ?>)
        </p>
        <label>Motivo:
            <input type="text" name="motivo" maxlength="500" size="40" required>
        </label>
        <label>Digite o nome do bônus:
            <input type="text" name="confirmacao" size="30" placeholder="<?php echo htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </label>
        <button type="submit">Cancelar todos</button>
    </form>
    <form method="post" action="<?php echo url('bonus/reverterAnulacaoEmLote/' . (int) $evento['id'] . '/' . (int) $item['id']); ?>" onsubmit="return confirm('Restabelecer os créditos que o Administrador cancelou neste bônus?');"><?= campoCsrf() ?>
        <button type="submit">Desfazer o cancelamento deste bônus</button>
    </form>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (empty($creditos)): ?>
    <p>Nenhum crédito de bônus neste evento ainda.</p>
<?php else: ?>
    <?php foreach ($creditos as $credito): ?>
    <div class="admin-card">
        <p>
            <strong><?php echo htmlspecialchars($credito['participante_nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
            <small><?php echo htmlspecialchars($credito['participante_email'], ENT_QUOTES, 'UTF-8'); ?></small>
            <?php if ($credito['anulado_em'] === null): ?>
                <span class="status-pill verde"><?php echo (int) $credito['pontos_creditados']; ?> pontos</span>
            <?php else: ?>
                <span class="status-pill vermelho">Anulado</span>
            <?php endif; ?>
        </p>
        <p>
            <?php echo htmlspecialchars($credito['bonus_nome'], ENT_QUOTES, 'UTF-8'); ?>
            · exigência atingida: <?php echo (int) $credito['exigencia_atingida']; ?>
            · <?php echo htmlspecialchars(formatarDataHora($credito['creditado_em']), ENT_QUOTES, 'UTF-8'); ?>
        </p>
        <?php if ($credito['anulado_em'] !== null): ?>
            <?php $anuladoPeloSistema = $credito['anulado_por'] === null; ?>
            <p style="color:#555;">
                <?php if ($anuladoPeloSistema): ?>
                    Anulado pelo sistema
                <?php else: ?>
                    Anulado por <?php echo htmlspecialchars((string) $credito['anulado_por_nome'], ENT_QUOTES, 'UTF-8'); ?>
                <?php endif; ?>
                em <?php echo htmlspecialchars(formatarDataHora($credito['anulado_em']), ENT_QUOTES, 'UTF-8'); ?>.
                Motivo: <?php echo htmlspecialchars((string) $credito['motivo_anulacao'], ENT_QUOTES, 'UTF-8'); ?>
                <?php if ($anuladoPeloSistema): ?>
                    <br><small>Volta sozinho se a presença removida for registrada de novo.</small>
                <?php endif; ?>
            </p>
            <?php if ($podeEditar): ?>
            <form method="post" action="<?php echo url('bonus/reverterAnulacao/' . (int) $evento['id'] . '/' . (int) $credito['id']); ?>" onsubmit="return confirm('Desfazer a anulação e devolver os pontos?');"><?= campoCsrf() ?>
                <button type="submit">Desfazer a anulação</button>
            </form>
            <?php endif; ?>
        <?php elseif ($podeEditar): ?>
            <form method="post" action="<?php echo url('bonus/anular/' . (int) $evento['id'] . '/' . (int) $credito['id']); ?>" onsubmit="return confirm('Anular este crédito? A pessoa é avisada, e ele não volta sozinho.');"><?= campoCsrf() ?>
                <label>Motivo da anulação (o participante lê este texto):
                    <input type="text" name="motivo" maxlength="500" size="60" required>
                </label>
                <button type="submit">Anular</button>
            </form>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
