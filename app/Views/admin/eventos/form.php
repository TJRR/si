<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1><?php echo ($evento === null || !isset($evento['id'])) ? 'Novo evento' : 'Editar evento'; ?></h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php $ehEdicao = $evento !== null && isset($evento['id']); ?>
<form method="post" action="<?php echo $ehEdicao ? url('eventos/editar/' . (int) $evento['id']) : url('eventos/novo'); ?>"><?= campoCsrf() ?>
    <label>Nome do evento:
        <input type="text" name="nome" required value="<?php echo htmlspecialchars($evento !== null ? (string) $evento['nome'] : '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="150" size="60">
    </label><br>

    <label>Descrição:<br>
        <textarea name="descricao" rows="4" cols="60"><?php echo htmlspecialchars($evento !== null ? (string) $evento['descricao'] : '', ENT_QUOTES, 'UTF-8'); ?></textarea>
    </label><br>

    <fieldset>
        <legend>Mensagem de confirmação da inscrição (enviada por e-mail ao participante)</legend>
        <?php
        $nome = 'mensagem_confirmacao_inscricao';
        $valor = $evento !== null ? (string) $evento['mensagem_confirmacao_inscricao'] : '';
        $rotulo = null;
        include __DIR__ . '/../_editor_rico.php';
        ?>
        <p style="color:#555;font-size:0.9em;">Em branco, o e-mail de confirmação usa um texto padrão.</p>
    </fieldset>

    <label>Data de início:
        <input type="date" name="data_inicio" required value="<?php echo htmlspecialchars($evento !== null ? (string) $evento['data_inicio'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Data de fim:
        <input type="date" name="data_fim" required value="<?php echo htmlspecialchars($evento !== null ? (string) $evento['data_fim'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Situação:
        <select name="status">
            <?php $statusAtual = $evento !== null ? $evento['status'] : 'ativo'; ?>
            <option value="ativo" <?php echo $statusAtual === 'ativo' ? 'selected' : ''; ?>>Ativo (aparece na inscrição pública)</option>
            <option value="encerrado" <?php echo $statusAtual === 'encerrado' ? 'selected' : ''; ?>>Encerrado</option>
        </select>
    </label><br>

    <label>Modo de credenciamento:
        <select name="modo_credenciamento">
            <?php $modoAtual = $evento !== null && isset($evento['modo_credenciamento']) ? $evento['modo_credenciamento'] : 'automatico'; ?>
            <option value="automatico" <?php echo $modoAtual === 'automatico' ? 'selected' : ''; ?>>Automático (todo inscrito já credenciado)</option>
            <option value="assistido" <?php echo $modoAtual === 'assistido' ? 'selected' : ''; ?>>Assistido (Administrador homologa cada inscrição em "Inscritos")</option>
        </select>
    </label>
    <p style="color:#555;font-size:0.9em;">
        No modo "Assistido", cada inscrição fica pendente até um administrador
        homologá-la na sub-aba "Inscritos": use quando o credenciamento
        precisa de conferência manual antes de liberar o participante.
    </p>

    <div class="form-acoes">
        <a href="<?php echo url('eventos/index'); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>
