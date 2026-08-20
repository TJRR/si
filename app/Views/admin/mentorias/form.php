<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
$edicao = $horario !== null;
$acao = $edicao
    ? url('mentoriaAdmin/editar/' . (int) $horario['id'])
    : url('mentoriaAdmin/novo/' . (int) $concurso['id']);

// Fase 31: elegibilidade inicial calculada em PHP a partir do mentor
// pre-selecionado (o proprio usuario logado, ver 'selected' abaixo) - o
// onchange do select so' precisa recalcular quando o admin escolhe OUTRO
// mentor, sem depender de um <script> disparar no load.
$mentorPadraoId = $edicao ? (int) $horario['mentor_usuario_id'] : (int) \App\Core\Auth::usuarioId();
$mentorPadraoElegivel = false;
$mentorPadraoNome = '—';
foreach ($mentores as $mentor) {
    if ((int) $mentor['id'] === $mentorPadraoId) {
        $mentorPadraoElegivel = organizadorElegivelGoogle($mentor['email']);
        $mentorPadraoNome = $mentor['nome'];
        break;
    }
}
?>
<h1><?php echo $edicao ? 'Editar horário de mentoria' : 'Novo horário de mentoria'; ?> — <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if (!empty($erro)): ?>
    <p class="flash-mensagem vermelho"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo $acao; ?>"><?= campoCsrf() ?>
    <?php if ($edicao): ?>
        <p><strong>Mentor:</strong> <?php echo htmlspecialchars($mentorPadraoNome, ENT_QUOTES, 'UTF-8'); ?>
           — o mentor e a integração com o Google Agenda não mudam na edição. Para trocar qualquer um dos dois, remova o horário e crie outro.</p>
    <?php else: ?>
        <label>Mentor:
            <select name="mentor_usuario_id" id="mentor_usuario_id" required onchange="var op=this.options[this.selectedIndex]; var el=op.getAttribute('data-google-elegivel')==='1'; var cb=document.getElementById('integracao_google'); cb.disabled=!el; if(!el){cb.checked=false; document.getElementById('link_meet').disabled=false;} document.getElementById('aviso-google-inelegivel').style.display = el ? 'none' : 'block';">
                <?php foreach ($mentores as $mentor): ?>
                    <option value="<?php echo (int) $mentor['id']; ?>" data-google-elegivel="<?php echo organizadorElegivelGoogle($mentor['email']) ? '1' : '0'; ?>" <?php echo (int) $mentor['id'] === $mentorPadraoId ? 'selected' : ''; ?>><?php echo htmlspecialchars($mentor['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </label><br>
    <?php endif; ?>

    <label>Início:
        <input type="datetime-local" name="data_inicio" required value="<?php echo htmlspecialchars($entrada['data_inicio'], ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Fim:
        <input type="datetime-local" name="data_fim" required value="<?php echo htmlspecialchars($entrada['data_fim'], ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <?php require __DIR__ . '/../_campo_etapa_evento.php'; ?>

    <?php if ($edicao): ?>
        <input type="hidden" name="integracao_google" value="<?php echo !empty($horario['integracao_google']) ? '1' : ''; ?>">
        <p><strong>Google Agenda:</strong> <?php echo !empty($horario['integracao_google']) ? 'integrado — o evento e a sala do Meet são atualizados automaticamente ao salvar.' : 'não integrado.'; ?></p>
    <?php else: ?>
        <label>
            <input type="checkbox" name="integracao_google" id="integracao_google" value="1" <?php echo $mentorPadraoElegivel ? '' : 'disabled'; ?> onchange="document.getElementById('link_meet').disabled = this.checked; if (this.checked) { document.getElementById('link_meet').value = ''; }">
            Integrar com Google Agenda (cria o evento e a sala do Meet automaticamente na agenda do mentor, e convida a equipe ao reservar)
        </label>
        <p id="aviso-google-inelegivel" style="<?php echo $mentorPadraoElegivel ? 'display:none;' : ''; ?> color:#666;">Disponível apenas para mentores com e-mail institucional @tjrr.jus.br.</p>
        <br>
    <?php endif; ?>

    <?php if (!$edicao || empty($horario['integracao_google'])): ?>
        <label>Link do Google Meet (opcional — sala criada previamente; ignorado se a integração acima estiver marcada):
            <input type="url" name="link_meet" id="link_meet" maxlength="255" placeholder="https://meet.google.com/xxx-xxxx-xxx" value="<?php echo htmlspecialchars($entrada['link_meet'], ENT_QUOTES, 'UTF-8'); ?>">
        </label><br>
    <?php endif; ?>

    <label>Observação (opcional — foco/tema deste horário):
        <input type="text" name="observacao" maxlength="255" placeholder="Ex.: Mentoria técnica — arquitetura de software" value="<?php echo htmlspecialchars($entrada['observacao'], ENT_QUOTES, 'UTF-8'); ?>">
    </label>

    <div class="form-acoes">
        <a href="<?php echo url('mentoriaAdmin/index/' . (int) $concurso['id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>
