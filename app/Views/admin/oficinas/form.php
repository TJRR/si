<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
$edicao = $horario !== null;
$acao = $edicao
    ? url('oficinaAdmin/editar/' . (int) $horario['id'])
    : url('oficinaAdmin/novo/' . (int) $concurso['id']);
?>
<h1><?php echo $edicao ? 'Editar horário de oficina' : 'Novo horário de oficina'; ?> — <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if (!empty($erro)): ?>
    <p class="flash-mensagem vermelho"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo $acao; ?>"><?= campoCsrf() ?>
    <label>Tema:
        <input type="text" name="tema" maxlength="255" required placeholder="Ex.: Como estruturar o pitch da sua ideia" value="<?php echo htmlspecialchars($entrada['tema'], ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Início:
        <input type="datetime-local" name="data_inicio" required value="<?php echo htmlspecialchars($entrada['data_inicio'], ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Fim:
        <input type="datetime-local" name="data_fim" required value="<?php echo htmlspecialchars($entrada['data_fim'], ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <?php require __DIR__ . '/../_campo_etapa_evento.php'; ?>

    <?php if ($edicao): ?>
        <input type="hidden" name="integracao_google" value="<?php echo !empty($horario['integracao_google']) ? '1' : ''; ?>">
        <p><strong>Google Agenda:</strong> <?php echo !empty($horario['integracao_google']) ? 'integrado — o evento e a sala do Meet são atualizados automaticamente ao salvar.' : 'não integrado.'; ?>
           A integração não muda na edição; para trocar, remova o horário e crie outro.</p>
    <?php else: ?>
        <label>
            <input type="checkbox" name="integracao_google" id="integracao_google" value="1" <?php echo !empty($organizadorElegivel) ? '' : 'disabled'; ?> onchange="document.getElementById('link_meet').disabled = this.checked; if (this.checked) { document.getElementById('link_meet').value = ''; }">
            Integrar com Google Agenda (cria o evento e a sala do Meet automaticamente na sua agenda, e convida as equipes ao se inscreverem)
        </label>
        <?php if (empty($organizadorElegivel)): ?>
            <p style="color:#666;">Disponível apenas para quem loga com e-mail institucional @tjrr.jus.br.</p>
        <?php endif; ?>
        <br>
    <?php endif; ?>

    <?php if (!$edicao || empty($horario['integracao_google'])): ?>
        <label>Link do Google Meet (opcional — sala criada previamente; ignorado se a integração acima estiver marcada):
            <input type="url" name="link_meet" id="link_meet" maxlength="255" placeholder="https://meet.google.com/xxx-xxxx-xxx" value="<?php echo htmlspecialchars($entrada['link_meet'], ENT_QUOTES, 'UTF-8'); ?>">
        </label><br>
    <?php endif; ?>

    <label>Observação (opcional — foco/tema deste horário):
        <input type="text" name="observacao" maxlength="255" placeholder="Ex.: Aberto a todas as trilhas" value="<?php echo htmlspecialchars($entrada['observacao'], ENT_QUOTES, 'UTF-8'); ?>">
    </label>

    <div class="form-acoes">
        <a href="<?php echo url('oficinaAdmin/index/' . (int) $concurso['id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>
