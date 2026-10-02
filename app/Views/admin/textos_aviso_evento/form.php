<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Texto do aviso: <?php echo htmlspecialchars($aviso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('textosAvisoEvento/index'); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<p style="color:#555;font-size:0.9em;"><?php echo htmlspecialchars($aviso['quando'], ENT_QUOTES, 'UTF-8'); ?></p>

<?php if ($gravado === null): ?>
    <p><span class="status-pill">Texto padrão</span> O formulário abaixo já vem com o texto que o sistema usa hoje. Nada muda até você salvar.</p>
<?php else: ?>
    <p><span class="status-pill verde">Texto próprio</span> Este aviso sai com o texto abaixo.</p>
<?php endif; ?>

<?php if (!empty($problemas)): ?>
    <div class="flash-mensagem erro">
        <p>O texto não foi salvo:</p>
        <ul>
            <?php foreach ($problemas as $problema): ?>
                <li><?php echo htmlspecialchars($problema, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<section class="admin-card">
    <h2>Palavras-chave deste aviso</h2>
    <p>Escreva a palavra-chave entre colchetes duplos, como <code>[[nome]]</code>, e o sistema troca pelo valor de cada envio.</p>
    <table border="1" cellpadding="6">
        <tr><th>Palavra-chave</th><th>O que entra no lugar</th><th>Onde pode ir</th></tr>
        <?php foreach ($aviso['palavras'] as $palavra => $definicao): ?>
            <tr>
                <td><code>[[<?php echo htmlspecialchars($palavra, ENT_QUOTES, 'UTF-8'); ?>]]</code></td>
                <td><?php echo htmlspecialchars($definicao[1], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo $definicao[0] === 'html' ? 'Só no texto' : 'Assunto e texto'; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php foreach ($aviso['obrigatorias'] as $alternativas): ?>
        <p><strong>Obrigatório no texto:</strong>
            <?php echo htmlspecialchars(implode(' ou ', array_map(function ($palavra) {
                return '[[' . $palavra . ']]';
            }, $alternativas)), ENT_QUOTES, 'UTF-8'); ?>.
            Sem isso, quem recebe o aviso não consegue continuar.
        </p>
    <?php endforeach; ?>
</section>

<form method="post" action="<?php echo url('textosAvisoEvento/editar/' . $chave); ?>"><?= campoCsrf() ?>
    <label>Assunto:
        <input type="text" name="assunto" maxlength="<?php echo (int) $assuntoMaximo; ?>" size="80" required value="<?php echo htmlspecialchars($assunto, ENT_QUOTES, 'UTF-8'); ?>">
    </label>

    <?php
    $nome = 'corpo_html';
    $valor = $corpo;
    $rotulo = 'Texto do aviso';
    include __DIR__ . '/../_editor_rico.php';
    ?>

    <div class="form-acoes">
        <button type="submit">Salvar texto</button>
    </div>
</form>

<?php if ($gravado !== null): ?>
    <form method="post" action="<?php echo url('textosAvisoEvento/restaurar/' . $chave); ?>" onsubmit="return confirm('Voltar este aviso ao texto padrão do sistema? O texto próprio será apagado.');"><?= campoCsrf() ?>
        <button type="submit" class="btn-acao">Voltar ao texto padrão</button>
    </form>
<?php endif; ?>
