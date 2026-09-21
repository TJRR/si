<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Resultado de Trabalhos: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('trabalhos/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<p style="color:#555;font-size:0.9em;">Prévia calculada em tempo real a partir das notas já lançadas. Nada é gravado até "Calcular e aplicar resultado" ser confirmado.</p>

<?php if (empty($ranking)): ?>
    <p>Nenhum trabalho para exibir ainda.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Posição</th><th>Título</th><th>Autor principal</th><th>Nota</th><th>Aprovação</th></tr>
        <?php foreach ($ranking as $posicao => $linha): ?>
        <tr>
            <td><?php echo $posicao + 1; ?></td>
            <td><?php echo htmlspecialchars($linha['titulo'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string) $linha['autor_principal_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo $linha['nota'] !== null ? htmlspecialchars(number_format($linha['nota'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') : 'sem nota lançada'; ?></td>
            <td><?php echo $linha['aprovado'] ? 'Aprovado' : 'Não aprovado'; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<form method="post" action="<?php echo url('trabalhos/resultado/' . (int) $evento['id']); ?>" onsubmit="return confirm('Calcular e gravar o resultado agora? Isso marca cada trabalho como aprovado, reprovado ou selecionado.');"><?= campoCsrf() ?>
    <button type="submit">Calcular e aplicar resultado</button>
</form>
