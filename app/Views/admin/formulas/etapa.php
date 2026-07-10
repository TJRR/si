<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Fórmula de pontuação — <?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('etapas/index/' . (int) $etapa['trilha_id']); ?>">Voltar às etapas</a></p>

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

<p>Define como a Nota da Etapa (NE) desta etapa é calculada, a partir das notas que serão lançadas para cada
    critério. Os pesos já são conhecidos agora — embuta o peso de cada critério como número na própria expressão.</p>

<?php if (empty($criteriosDaEtapa)): ?>
    <p>Nenhum critério cadastrado nesta etapa ainda. Cadastre os critérios em
        <a href="<?php echo url('criterios/index/' . (int) $etapa['id']); ?>">Critérios</a> antes de escrever a fórmula.</p>
<?php else: ?>
    <p>Variáveis disponíveis (código — nome — peso):</p>
    <ul>
        <?php foreach ($criteriosDaEtapa as $criterio): ?>
            <li><code><?php echo htmlspecialchars($criterio['codigo'], ENT_QUOTES, 'UTF-8'); ?></code>
                — <?php echo htmlspecialchars($criterio['nome'], ENT_QUOTES, 'UTF-8'); ?>
                — peso <?php echo number_format((float) $criterio['peso'], 2, ',', '.'); ?></li>
        <?php endforeach; ?>
    </ul>

    <form method="post" action="<?php echo url('formulas/etapa/' . (int) $etapa['id']); ?>">
        <label>Expressão (ex.: <?php
            $exemplo = [];
            foreach ($criteriosDaEtapa as $criterio) {
                $exemplo[] = $criterio['codigo'] . '*' . number_format((float) $criterio['peso'], 2, '.', '');
            }
            echo htmlspecialchars('(' . implode(' + ', $exemplo) . ') / 10', ENT_QUOTES, 'UTF-8');
        ?>):<br>
            <textarea name="expressao" rows="3" cols="70" required><?php echo htmlspecialchars((string) $expressaoAtual, ENT_QUOTES, 'UTF-8'); ?></textarea>
        </label><br>

        <p>Testar com valores de exemplo (nota que cada criterio teria):</p>
        <?php foreach ($criteriosDaEtapa as $criterio): ?>
            <label><?php echo htmlspecialchars($criterio['codigo'], ENT_QUOTES, 'UTF-8'); ?>:
                <input type="text" name="valores[<?php echo htmlspecialchars($criterio['codigo'], ENT_QUOTES, 'UTF-8'); ?>]"
                    value="<?php echo htmlspecialchars(isset($_POST['valores'][$criterio['codigo']]) ? $_POST['valores'][$criterio['codigo']] : (string) $criterio['escala_max'], ENT_QUOTES, 'UTF-8'); ?>">
            </label><br>
        <?php endforeach; ?>

        <button type="submit" name="acao" value="testar">Testar fórmula</button>
        <button type="submit" name="acao" value="salvar">Salvar</button>
    </form>
<?php endif; ?>
