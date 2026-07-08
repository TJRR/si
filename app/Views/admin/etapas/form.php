<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1><?php echo $etapa === null ? 'Nova etapa' : 'Editar etapa'; ?> — <?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('etapas/index/' . (int) $trilha['id']); ?>">Voltar</a></p>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo $etapa === null ? url('etapas/novo/' . (int) $trilha['id']) : url('etapas/editar/' . (int) $etapa['id']); ?>">
    <label>Nome:
        <input type="text" name="nome" required value="<?php echo htmlspecialchars($etapa !== null ? $etapa['nome'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Descricao:<br>
        <textarea name="descricao" rows="4" cols="50"><?php echo htmlspecialchars($etapa !== null ? (string) $etapa['descricao'] : '', ENT_QUOTES, 'UTF-8'); ?></textarea>
    </label><br>

    <label>Ordem:
        <input type="number" name="ordem" value="<?php echo $etapa !== null ? (int) $etapa['ordem'] : 0; ?>">
    </label><br>

    <label>Data de inicio:
        <input type="date" name="data_inicio" value="<?php echo htmlspecialchars($etapa !== null ? (string) $etapa['data_inicio'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Data de fim:
        <input type="date" name="data_fim" value="<?php echo htmlspecialchars($etapa !== null ? (string) $etapa['data_fim'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Regra de transicao para a proxima etapa:
        <select name="regra_transicao_tipo">
            <option value="">Nenhuma (etapa final ou sem corte)</option>
            <?php foreach (['numero_fixo' => 'Numero fixo de equipes classificadas', 'percentual' => 'Percentual classificado', 'nota_corte' => 'Nota de corte'] as $valor => $rotulo): ?>
                <?php $selecionado = ($etapa !== null && $etapa['regra_transicao_tipo'] === $valor); ?>
                <option value="<?php echo $valor; ?>" <?php echo $selecionado ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label><br>

    <label>Valor da regra de transicao (nº de equipes, % ou nota, conforme o tipo acima):
        <input type="text" name="regra_transicao_valor" value="<?php echo htmlspecialchars($etapa !== null && $etapa['regra_transicao_valor'] !== null ? (string) $etapa['regra_transicao_valor'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Formulario dinamico vinculado:
        <select name="formulario_dinamico_id">
            <option value="">Nenhum</option>
            <?php foreach ($formularios as $formulario): ?>
                <?php $selecionado = ($etapa !== null && (int) $etapa['formulario_dinamico_id'] === (int) $formulario['id']); ?>
                <option value="<?php echo (int) $formulario['id']; ?>" <?php echo $selecionado ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($formulario['nome'], ENT_QUOTES, 'UTF-8'); ?>
                    (v<?php echo (int) $formulario['versao']; ?>, <?php echo htmlspecialchars($formulario['status'], ENT_QUOTES, 'UTF-8'); ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </label><br>

    <button type="submit">Salvar</button>
</form>
