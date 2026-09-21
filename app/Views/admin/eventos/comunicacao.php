<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Comunicação: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('eventos/index'); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<fieldset>
    <legend>Novo aviso</legend>
    <form method="post" action="<?php echo url('eventos/comunicacaoEnviar'); ?>"><?= campoCsrf() ?>
        <input type="hidden" name="evento_id" value="<?php echo (int) $evento['id']; ?>">

        <label>Assunto:
            <input type="text" name="assunto" required maxlength="200" size="60">
        </label><br>

        <fieldset>
            <legend>Mensagem</legend>
            <?php
            $nome = 'corpo_html';
            $valor = '';
            $rotulo = null;
            include __DIR__ . '/../_editor_rico.php';
            ?>
        </fieldset>

        <?php if (empty($inscricoes)): ?>
            <p>Nenhum inscrito neste evento ainda.</p>
        <?php else: ?>
            <p>
                <label><input type="checkbox" id="comunicacao-marcar-todos" checked> Marcar/desmarcar todos</label>
            </p>
            <table border="1" cellpadding="6">
                <tr><th></th><th>Nome</th><th>E-mail</th></tr>
                <?php foreach ($inscricoes as $inscricao): ?>
                <tr>
                    <td><input type="checkbox" class="comunicacao-destinatario" name="destinatarios[]" value="<?php echo (int) $inscricao['id']; ?>" checked></td>
                    <td><?php echo htmlspecialchars($inscricao['usuario_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($inscricao['usuario_email'], ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <div class="form-acoes">
                <button type="submit">Enviar aviso</button>
            </div>
        <?php endif; ?>
    </form>
</fieldset>

<script>
(function () {
    var marcarTodos = document.getElementById('comunicacao-marcar-todos');
    if (!marcarTodos) {
        return;
    }
    marcarTodos.addEventListener('change', function () {
        var caixas = document.querySelectorAll('.comunicacao-destinatario');
        for (var i = 0; i < caixas.length; i++) {
            caixas[i].checked = marcarTodos.checked;
        }
    });
})();
</script>

<h2>Histórico de avisos</h2>
<?php if (empty($campanhas)): ?>
    <p>Nenhum aviso enviado ainda.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr>
            <th>Data</th><th>Autor</th><th>Assunto</th><th>Destinatários</th>
            <th>Enviados</th><th>Falhas</th><th>Situação</th><th>Mensagem</th>
        </tr>
        <?php foreach ($campanhas as $campanha): ?>
        <tr>
            <td><?php echo htmlspecialchars(formatarDataHora($campanha['criado_em']), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($campanha['autor_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($campanha['assunto'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo (int) $campanha['total_destinatarios']; ?></td>
            <td><?php echo (int) $campanha['total_enviados']; ?></td>
            <td><?php echo (int) $campanha['total_falhas']; ?></td>
            <td>
                <?php if ($campanha['concluido_em'] !== null): ?>
                    Concluído em <?php echo htmlspecialchars(formatarDataHora($campanha['concluido_em']), ENT_QUOTES, 'UTF-8'); ?>
                <?php else: ?>
                    Em andamento
                <?php endif; ?>
            </td>
            <td>
                <details>
                    <summary class="btn-icone" title="Ver mensagem enviada" style="display:inline-flex;cursor:pointer;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </summary>
                    <div style="max-width:400px;margin-top:6px;"><?php echo $campanha['corpo_html']; ?></div>
                </details>
            </td>
        </tr>
        <?php if ((int) $campanha['total_falhas'] > 0 && !empty($falhasPorCampanha[$campanha['id']])): ?>
        <tr>
            <td colspan="8">
                <strong>Falharam:</strong>
                <?php
                $nomesFalhas = array_map(function ($falha) {
                    return htmlspecialchars($falha['usuario_nome'], ENT_QUOTES, 'UTF-8')
                        . ' (' . htmlspecialchars($falha['usuario_email'], ENT_QUOTES, 'UTF-8') . ')';
                }, $falhasPorCampanha[$campanha['id']]);
                echo implode(', ', $nomesFalhas);
                ?>
            </td>
        </tr>
        <?php endif; ?>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
