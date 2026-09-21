<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Eixos temáticos: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('trabalhos/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<p style="color:#555;font-size:0.9em;">Se nenhum eixo for cadastrado, o campo não aparece no formulário de submissão.</p>

<?php if (empty($eixos)): ?>
    <p>Nenhum eixo cadastrado ainda.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Nome</th><th>Descrição</th><th>Ações</th></tr>
        <?php foreach ($eixos as $eixo): ?>
        <tr>
            <td><?php echo htmlspecialchars($eixo['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string) $eixo['descricao'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <form method="post" action="<?php echo url('trabalhos/eixoRemover/' . (int) $eixo['id']); ?>" onsubmit="return confirm('Remover este eixo temático?');"><?= campoCsrf() ?>
                    <button type="submit">Remover</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<h2>Novo eixo temático</h2>
<form method="post" action="<?php echo url('trabalhos/eixos/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
    <label>Nome: <input type="text" name="nome" required maxlength="150"></label>
    <label>Descrição: <input type="text" name="descricao" maxlength="255"></label>
    <button type="submit">Cadastrar eixo</button>
</form>
