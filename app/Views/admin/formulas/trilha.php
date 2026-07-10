<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Fórmula da nota final — <?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('trilhas/index/' . (int) $trilha['concurso_id']); ?>">Voltar às trilhas</a></p>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php if ($resultadoTeste !== null): ?>
    <?php if ($resultadoTeste['sucesso']): ?>
        <p style="color:green;">Resultado do teste: <strong><?php echo number_format($resultadoTeste['valor'], 4, ',', '.'); ?></strong></p>
    <?php else: ?>
        <p style="color:red;">Erro ao testar: <?php echo htmlspecialchars($resultadoTeste['mensagem'], ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
<?php endif; ?>

<p>Define como a Nota Final (NF) da trilha é calculada a partir das notas das etapas. Ex.: Editais 12/2026 e 13/2026
    usam NF = NE2 x 0,4 + NE3 x 0,6.</p>

<?php if (empty($etapasDaTrilha)): ?>
    <p>Nenhuma etapa cadastrada nesta trilha ainda.</p>
<?php else: ?>
    <p>Variáveis disponíveis (nota de cada etapa, pela ordem):</p>
    <ul>
        <?php foreach ($etapasDaTrilha as $etapaDaTrilha): ?>
            <li><code>NE<?php echo (int) $etapaDaTrilha['ordem']; ?></code>
                — <?php echo htmlspecialchars($etapaDaTrilha['nome'], ENT_QUOTES, 'UTF-8'); ?></li>
        <?php endforeach; ?>
    </ul>

    <form method="post" action="<?php echo url('formulas/trilha/' . (int) $trilha['id']); ?>">
        <label>Expressão (ex.: NE2*0.4 + NE3*0.6):<br>
            <textarea name="expressao" rows="3" cols="70" required><?php echo htmlspecialchars((string) $expressaoAtual, ENT_QUOTES, 'UTF-8'); ?></textarea>
        </label><br>

        <p>Testar com valores de exemplo (nota que cada etapa teria):</p>
        <?php foreach ($etapasDaTrilha as $etapaDaTrilha): ?>
            <?php $variavel = 'NE' . (int) $etapaDaTrilha['ordem']; ?>
            <label><?php echo htmlspecialchars($variavel, ENT_QUOTES, 'UTF-8'); ?>:
                <input type="text" name="valores[<?php echo htmlspecialchars($variavel, ENT_QUOTES, 'UTF-8'); ?>]"
                    value="<?php echo htmlspecialchars(isset($_POST['valores'][$variavel]) ? $_POST['valores'][$variavel] : '10', ENT_QUOTES, 'UTF-8'); ?>">
            </label><br>
        <?php endforeach; ?>

        <button type="submit" name="acao" value="testar">Testar fórmula</button>
        <button type="submit" name="acao" value="salvar">Salvar</button>
    </form>
<?php endif; ?>
