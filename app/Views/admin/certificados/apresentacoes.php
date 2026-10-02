<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 59: a marca de trabalho efetivamente apresentado. Sem ela nenhum autor
 * recebe certificado de apresentacao.
 *
 * Sem barra de filtros, de proposito: a selecao para apresentacao e' um
 * recorte pequeno dos trabalhos aprovados, e filtro em lista desse tamanho
 * so' atrapalha (licao da Fase 58, "a tela pergunta so' o que tem mais de uma
 * resposta possivel").
 *
 * NENHUM texto desta tela cita edital, item de norma nem regra de uma edicao
 * especifica (premissa 1). A regra do evento vive no documento publicado pela
 * organizacao, e a tela so' descreve o que o sistema faz.
 */
$pendentes = count($trabalhos) - (int) $apresentados;
?>
<div class="pagina-titulo-acoes">
    <h1>Apresentações de trabalho: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
</div>

<?php if (!$podeEditar): ?>
    <p class="status-pill laranja">Somente leitura: só o Administrador marca quem apresentou.</p>
<?php endif; ?>

<div class="admin-card">
    <p>
        Marque aqui quem de fato apresentou o trabalho no evento. Só os autores de trabalho marcado recebem o
        certificado de apresentação, e o certificado sai para <strong>todos</strong> os autores do trabalho,
        não apenas para quem ficou junto ao cartaz de exposição.
    </p>
    <p><small>
        Retirar a marca depois não desfaz certificado já emitido: por decisão da organização o documento é
        guardado como foi emitido. Para desfazer uma emissão, cancele o documento na aba "Emitidos".
    </small></p>
</div>

<?php if (empty($trabalhos)): ?>
    <p>
        Nenhum trabalho selecionado para apresentação neste evento. A seleção acontece quando a organização
        publica o resultado em Trabalhos, Resultado.
    </p>
<?php else: ?>
    <div class="auditoria-resumo">
        <span>
            <?php echo (int) $apresentados; ?> de <?php echo count($trabalhos); ?> marcado(s) como apresentado(s)
            <?php if ($pendentes > 0): ?>
                · <?php echo $pendentes; ?> sem marca
            <?php endif; ?>
        </span>
    </div>

    <?php if ($podeEditar): ?>
        <form method="post" id="form-acoes-em-massa"><?= campoCsrf() ?></form>
    <?php endif; ?>

    <div class="tabela-scroll">
        <table border="1" cellpadding="6">
            <tr>
                <?php if ($podeEditar): ?>
                    <th><input type="checkbox" id="marcar-todos"></th>
                <?php endif; ?>
                <th>Posição</th>
                <th>Trabalho</th>
                <th>Nota</th>
                <th>Apresentou</th>
                <th>Observação</th>
            </tr>
            <?php foreach ($trabalhos as $trabalho): ?>
                <?php $apresentou = $trabalho['apresentado_em'] !== null; ?>
                <tr>
                    <?php if ($podeEditar): ?>
                        <td><input type="checkbox" class="marcar-linha" name="ids[]" value="<?php echo (int) $trabalho['id']; ?>" form="form-acoes-em-massa"></td>
                    <?php endif; ?>
                    <td><?php echo $trabalho['posicao'] !== null ? (int) $trabalho['posicao'] : ''; ?></td>
                    <td><?php echo htmlspecialchars($trabalho['titulo'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo $trabalho['nota_final'] !== null ? htmlspecialchars(number_format((float) $trabalho['nota_final'], $casasDecimais, ',', ''), ENT_QUOTES, 'UTF-8') : ''; ?></td>
                    <td>
                        <?php if ($apresentou): ?>
                            <span class="status-pill verde">Sim</span>
                            <br><small>
                                <?php echo formatarData($trabalho['apresentado_em']); ?>
                                <?php if (!empty($trabalho['apresentado_por_nome'])): ?>
                                    por <?php echo htmlspecialchars($trabalho['apresentado_por_nome'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php endif; ?>
                            </small>
                        <?php else: ?>
                            <span class="status-pill laranja">Sem marca</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars((string) $trabalho['observacao'], ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <?php if ($podeEditar): ?>
        <p>Com os selecionados:</p>
        <p>
            <label>Observação, opcional
                <input type="text" name="observacao" maxlength="255" size="40" form="form-acoes-em-massa">
            </label>
            <button type="submit" form="form-acoes-em-massa" formaction="<?php echo url('certificados/marcarApresentados/' . (int) $evento['id']); ?>"
                    onclick="return confirm('Marcar os trabalhos selecionados como apresentados?');">Marcar como apresentados</button>
            <button type="submit" form="form-acoes-em-massa" formaction="<?php echo url('certificados/desmarcarApresentados/' . (int) $evento['id']); ?>"
                    onclick="return confirm('Retirar a marca dos trabalhos selecionados? Certificado já emitido continua valendo.');">Retirar a marca</button>
        </p>
    <?php endif; ?>
<?php endif; ?>
