<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Critérios de avaliação: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('trabalhos/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<p style="color:#555;font-size:0.9em;">A nota máxima de um trabalho é a soma da nota máxima de cada critério (hoje: <?php echo htmlspecialchars(number_format($notaMaximaTotal, 2, ',', '.'), ENT_QUOTES, 'UTF-8'); ?>).</p>

<?php if (empty($criterios)): ?>
    <p>Nenhum critério cadastrado ainda.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Ordem</th><th>Nome</th><th>Descrição</th><th>Nota máxima</th><th>Ações</th></tr>
        <?php foreach ($criterios as $criterio): ?>
        <tr>
            <td>
                <form method="post" action="<?php echo url('trabalhos/criterioMover/' . (int) $criterio['id'] . '/cima'); ?>" style="display:inline;"><?= campoCsrf() ?><button type="submit" title="Mover para cima">▲</button></form>
                <form method="post" action="<?php echo url('trabalhos/criterioMover/' . (int) $criterio['id'] . '/baixo'); ?>" style="display:inline;"><?= campoCsrf() ?><button type="submit" title="Mover para baixo">▼</button></form>
            </td>
            <td><?php echo htmlspecialchars($criterio['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string) $criterio['descricao'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($criterio['nota_maxima'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <form method="post" action="<?php echo url('trabalhos/criterioRemover/' . (int) $criterio['id']); ?>" onsubmit="return confirm('Remover este critério?');"><?= campoCsrf() ?>
                    <button type="submit">Remover</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<h2>Novo critério</h2>
<form method="post" action="<?php echo url('trabalhos/criterios/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
    <input type="hidden" name="acao" value="criar_criterio">
    <label>Nome: <input type="text" name="nome" required maxlength="150"></label>
    <label>Descrição: <input type="text" name="descricao" maxlength="255"></label>
    <label>Nota máxima: <input type="number" step="0.01" name="nota_maxima" value="2.00" required></label>
    <button type="submit">Cadastrar critério</button>
</form>

<h2>Resumo para o avaliador</h2>
<p style="color:#555;font-size:0.9em;">Texto que o avaliador consulta dentro da tela de avaliação (nome de cada critério, faixa de nota e como interpretar) - não é o edital inteiro, só este resumo.</p>
<form method="post" action="<?php echo url('trabalhos/criterios/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
    <input type="hidden" name="acao" value="salvar_resumo">
    <?php $nome = 'criterios_resumo_html'; $valor = $criteriosResumoHtml; $rotulo = null; ?>
    <?php include __DIR__ . '/../_editor_rico.php'; ?>
    <button type="submit">Salvar resumo</button>
</form>
