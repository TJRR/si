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

    <label>Descrição:<br>
        <textarea name="descricao" rows="4" cols="50"><?php echo htmlspecialchars($etapa !== null ? (string) $etapa['descricao'] : '', ENT_QUOTES, 'UTF-8'); ?></textarea>
    </label><br>

    <label>Ordem:
        <input type="number" name="ordem" value="<?php echo $etapa !== null ? (int) $etapa['ordem'] : 0; ?>">
    </label><br>

    <label>Data de início:
        <input type="date" name="data_inicio" value="<?php echo htmlspecialchars($etapa !== null ? (string) $etapa['data_inicio'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Data de fim:
        <input type="date" name="data_fim" value="<?php echo htmlspecialchars($etapa !== null ? (string) $etapa['data_fim'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Regra de transição para a próxima etapa:
        <select name="regra_transicao_tipo">
            <option value="">Nenhuma (etapa final ou sem corte)</option>
            <?php foreach (['numero_fixo' => 'Número fixo de equipes classificadas', 'percentual' => 'Percentual classificado', 'nota_corte' => 'Nota de corte'] as $valor => $rotulo): ?>
                <?php $selecionado = ($etapa !== null && $etapa['regra_transicao_tipo'] === $valor); ?>
                <option value="<?php echo $valor; ?>" <?php echo $selecionado ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label><br>

    <label>Valor da regra de transição (nº de equipes, % ou nota, conforme o tipo acima):
        <input type="text" name="regra_transicao_valor" value="<?php echo htmlspecialchars($etapa !== null && $etapa['regra_transicao_valor'] !== null ? (string) $etapa['regra_transicao_valor'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Formulário dinâmico vinculado:
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

    <label>Mecanismo de avaliação:
        <select name="mecanismo_avaliacao" id="campo-mecanismo-avaliacao">
            <?php foreach (['nenhuma' => 'Nenhuma', 'administrador' => 'Pelo Administrador (ex.: homologação de cadastro)', 'avaliadores' => 'Por Avaliadores'] as $valor => $rotulo): ?>
                <?php $selecionado = ($etapa !== null && $etapa['mecanismo_avaliacao'] === $valor); ?>
                <option value="<?php echo $valor; ?>" <?php echo $selecionado ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label><br>

    <fieldset id="fieldset-avaliacao-por-avaliadores">
        <legend>Configuração de avaliação desta etapa</legend>

        <label>Designação de avaliadores:
            <select name="modo_designacao">
                <?php foreach (['' => 'Não definido', 'manual' => 'Admin atribui manualmente', 'aberto' => 'Todo avaliador da trilha vê tudo', 'automatico' => 'Distribuição automática balanceada', 'sorteio_categoria' => 'Sorteio aleatório garantindo 1 avaliador de cada categoria'] as $valor => $rotulo): ?>
                    <?php $selecionado = ($etapa !== null && (string) $etapa['modo_designacao'] === (string) $valor); ?>
                    <option value="<?php echo $valor; ?>" <?php echo $selecionado ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label><br>

        <label>Quantidade de avaliadores por submissão (usada em "atribui manualmente"/"distribuição automática"; ignorada em "sorteio por categoria", que usa a tela "Vagas por categoria" da etapa):
            <input type="number" name="qtd_avaliadores_por_submissao" min="1" value="<?php echo $etapa !== null ? (int) $etapa['qtd_avaliadores_por_submissao'] : 1; ?>">
        </label><br>

        <label>Consolidação quando mais de um avaliador nota a mesma submissão:
            <select name="modo_consolidacao">
                <?php foreach (['unico' => 'Só 1 avaliador esperado', 'media_criterio' => 'Média por critério, fórmula roda 1x', 'media_ne' => 'Média das notas finais (NE) individuais'] as $valor => $rotulo): ?>
                    <?php $selecionado = ($etapa !== null && $etapa['modo_consolidacao'] === $valor); ?>
                    <option value="<?php echo $valor; ?>" <?php echo $selecionado ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label><br>

        <label>Sigilo da avaliação:
            <select name="modo_sigilo">
                <?php foreach (['aberto' => 'Aberta (avaliador vê a equipe)', 'cego' => 'Cega (avaliador não vê de quem é a submissão)'] as $valor => $rotulo): ?>
                    <?php $selecionado = ($etapa !== null && $etapa['modo_sigilo'] === $valor); ?>
                    <option value="<?php echo $valor; ?>" <?php echo $selecionado ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label><br>

        <label>Avanço para a próxima etapa:
            <select name="modo_avanco">
                <?php foreach (['manual' => 'Manual (Admin confirma o corte antes de liberar)', 'automatico' => 'Automático pelo ranking assim que as notas estiverem completas'] as $valor => $rotulo): ?>
                    <?php $selecionado = ($etapa !== null && $etapa['modo_avanco'] === $valor); ?>
                    <option value="<?php echo $valor; ?>" <?php echo $selecionado ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </fieldset><br>

    <button type="submit">Salvar</button>
</form>

<script>
(function () {
    var select = document.getElementById('campo-mecanismo-avaliacao');
    var fieldset = document.getElementById('fieldset-avaliacao-por-avaliadores');

    function atualizar() {
        fieldset.style.display = select.value === 'avaliadores' ? '' : 'none';
    }

    select.addEventListener('change', atualizar);
    atualizar();
})();
</script>
