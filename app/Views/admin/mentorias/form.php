<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Novo horário de mentoria — <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php
// Fase 31: elegibilidade inicial calculada em PHP a partir do mentor
// pre-selecionado (o proprio usuario logado, ver 'selected' abaixo) - o
// onchange do select so' precisa recalcular quando o admin escolhe OUTRO
// mentor, sem depender de um <script> disparar no load.
$mentorPadraoId = (int) \App\Core\Auth::usuarioId();
$mentorPadraoElegivel = false;
foreach ($mentores as $mentor) {
    if ((int) $mentor['id'] === $mentorPadraoId) {
        $mentorPadraoElegivel = organizadorElegivelGoogle($mentor['email']);
        break;
    }
}
?>
<form method="post" action="<?php echo url('mentoriaAdmin/novo/' . (int) $concurso['id']); ?>"><?= campoCsrf() ?>
    <label>Mentor:
        <select name="mentor_usuario_id" id="mentor_usuario_id" required onchange="var op=this.options[this.selectedIndex]; var el=op.getAttribute('data-google-elegivel')==='1'; var cb=document.getElementById('integracao_google'); cb.disabled=!el; if(!el){cb.checked=false; document.getElementById('link_meet').disabled=false;} document.getElementById('aviso-google-inelegivel').style.display = el ? 'none' : 'block';">
            <?php foreach ($mentores as $mentor): ?>
                <option value="<?php echo (int) $mentor['id']; ?>" data-google-elegivel="<?php echo organizadorElegivelGoogle($mentor['email']) ? '1' : '0'; ?>" <?php echo (int) $mentor['id'] === $mentorPadraoId ? 'selected' : ''; ?>><?php echo htmlspecialchars($mentor['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
        </select>
    </label><br>

    <label>Início:
        <input type="datetime-local" name="data_inicio" required>
    </label><br>

    <label>Fim:
        <input type="datetime-local" name="data_fim" required>
    </label><br>

    <label>
        <input type="checkbox" name="integracao_google" id="integracao_google" value="1" <?php echo $mentorPadraoElegivel ? '' : 'disabled'; ?> onchange="document.getElementById('link_meet').disabled = this.checked; if (this.checked) { document.getElementById('link_meet').value = ''; }">
        Integrar com Google Agenda (cria o evento e a sala do Meet automaticamente na agenda do mentor, e convida a equipe ao reservar)
    </label>
    <p id="aviso-google-inelegivel" style="<?php echo $mentorPadraoElegivel ? 'display:none;' : ''; ?> color:#666;">Disponível apenas para mentores com e-mail institucional @tjrr.jus.br.</p>
    <br>

    <label>Link do Google Meet (opcional — sala criada previamente; ignorado se a integração acima estiver marcada):
        <input type="url" name="link_meet" id="link_meet" maxlength="255" placeholder="https://meet.google.com/xxx-xxxx-xxx">
    </label><br>

    <label>Observação (opcional — foco/tema deste horário):
        <input type="text" name="observacao" maxlength="255" placeholder="Ex.: Mentoria técnica — arquitetura de software">
    </label>

    <div class="form-acoes">
        <a href="<?php echo url('mentoriaAdmin/index/' . (int) $concurso['id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>
