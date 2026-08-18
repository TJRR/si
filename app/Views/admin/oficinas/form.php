<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Novo horário de oficina — <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('oficinaAdmin/novo/' . (int) $concurso['id']); ?>"><?= campoCsrf() ?>
    <label>Tema:
        <input type="text" name="tema" maxlength="255" required placeholder="Ex.: Como estruturar o pitch da sua ideia">
    </label><br>

    <label>Início:
        <input type="datetime-local" name="data_inicio" required>
    </label><br>

    <label>Fim:
        <input type="datetime-local" name="data_fim" required>
    </label><br>

    <label>
        <input type="checkbox" name="integracao_google" id="integracao_google" value="1" <?php echo !empty($organizadorElegivel) ? '' : 'disabled'; ?> onchange="document.getElementById('link_meet').disabled = this.checked; if (this.checked) { document.getElementById('link_meet').value = ''; }">
        Integrar com Google Agenda (cria o evento e a sala do Meet automaticamente na sua agenda, e convida as equipes ao se inscreverem)
    </label>
    <?php if (empty($organizadorElegivel)): ?>
        <p style="color:#666;">Disponível apenas para quem loga com e-mail institucional @tjrr.jus.br.</p>
    <?php endif; ?>
    <br>

    <label>Link do Google Meet (opcional — sala criada previamente; ignorado se a integração acima estiver marcada):
        <input type="url" name="link_meet" id="link_meet" maxlength="255" placeholder="https://meet.google.com/xxx-xxxx-xxx">
    </label><br>

    <label>Observação (opcional — foco/tema deste horário):
        <input type="text" name="observacao" maxlength="255" placeholder="Ex.: Aberto a todas as trilhas">
    </label>

    <div class="form-acoes">
        <a href="<?php echo url('oficinaAdmin/index/' . (int) $concurso['id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>
