<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Participações em competições: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('competicoes/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<?php if (!empty($gincanaEncerrada)): ?>
    <p class="status-pill laranja">A gincana deste evento foi encerrada e a classificação está congelada: nenhuma participação pode ser anulada ou restabelecida.</p>
<?php endif; ?>

<p style="color:#555;font-size:0.9em;">
    Anular uma participação tira os pontos e avisa a pessoa, com o motivo, por exemplo quando alguém leu o código
    de uma foto sem ter participado. A pessoa não consegue registrar a participação de novo na mesma competição;
    o conserto de um engano é desfazer a anulação.
</p>

<?php
$parametrosFiltro = array_filter([
    'busca' => $filtros['busca'],
    'competicao_id' => $filtros['competicao_id'] > 0 ? $filtros['competicao_id'] : '',
    'situacao' => $filtros['situacao'],
    'data_inicio' => $filtros['data_inicio'],
    'data_fim' => $filtros['data_fim'],
], function ($valor) {
    return $valor !== '' && $valor !== null;
});
$podeAgir = $podeEditar && empty($gincanaEncerrada);
?>
<div class="filtros-barra-wrapper">
    <form method="get" action="<?php echo config('base_path'); ?>/index.php" class="filtros-barra">
        <input type="hidden" name="r" value="competicoes/participacoes/<?php echo (int) $evento['id']; ?>">
        <label class="filtro-busca">Busca:
            <input type="text" name="busca" placeholder="Nome ou correio eletrônico" value="<?php echo htmlspecialchars($filtros['busca'], ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <?php if (count($competicoes) > 1): ?>
            <label>Competição:
                <select name="competicao_id">
                    <option value="">Todas</option>
                    <?php foreach ($competicoes as $competicao): ?>
                        <option value="<?php echo (int) $competicao['id']; ?>"<?php echo (int) $filtros['competicao_id'] === (int) $competicao['id'] ? ' selected' : ''; ?>><?php echo htmlspecialchars($competicao['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <label>Situação:
            <select name="situacao">
                <option value="">Todas</option>
                <option value="validas"<?php echo $filtros['situacao'] === 'validas' ? ' selected' : ''; ?>>Válidas</option>
                <option value="anuladas"<?php echo $filtros['situacao'] === 'anuladas' ? ' selected' : ''; ?>>Anuladas</option>
            </select>
        </label>
        <label>De:
            <input type="date" name="data_inicio" value="<?php echo htmlspecialchars($filtros['data_inicio'], ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <label>Até:
            <input type="date" name="data_fim" value="<?php echo htmlspecialchars($filtros['data_fim'], ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <div class="filtros-barra-acoes">
            <button type="submit" class="btn-icone" title="Filtrar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                </svg>
            </button>
            <a href="<?php echo url('competicoes/participacoes/' . (int) $evento['id']); ?>" class="btn-icone" title="Limpar filtros">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="1 4 1 10 7 10"></polyline>
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                </svg>
            </a>
        </div>
    </form>
</div>

<div class="auditoria-resumo">
    <span>
        <?php echo count($participacoes); ?>
        <?php echo count($participacoes) === 1 ? 'participação encontrada' : 'participações encontradas'; ?>
    </span>
    <?php if ($podeEditar && !empty($participacoes)): ?>
        <a href="<?php echo htmlspecialchars(url('competicoes/exportar/' . (int) $evento['id']) . ($parametrosFiltro !== [] ? '&' . http_build_query($parametrosFiltro) : ''), ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Exportar a lista filtrada">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
        </a>
    <?php endif; ?>
</div>

<?php if (empty($participacoes)): ?>
    <p>Nenhuma participação encontrada<?php echo $parametrosFiltro !== [] ? ' com estes filtros' : ' até o momento'; ?>.</p>
<?php else: ?>
    <?php if ($podeAgir): ?>
        <?php /* Formulário de lote vazio e fora da tabela: as caixas de cada linha
        se ligam a ele pelo atributo "form=", porque HTML não aceita formulário
        dentro de formulário. */ ?>
        <form method="post" id="form-acoes-em-massa"><?= campoCsrf() ?>
            <input type="hidden" name="evento_id" value="<?php echo (int) $evento['id']; ?>">
        </form>
    <?php endif; ?>

    <div class="tabela-scroll">
        <table border="1" cellpadding="6">
            <tr>
                <?php if ($podeAgir): ?>
                    <th><input type="checkbox" id="marcar-todos"></th>
                <?php endif; ?>
                <th>Participante</th>
                <th>Competição</th>
                <th>Participou em</th>
                <th>Pontos</th>
                <th>Situação</th>
                <?php if ($podeAgir): ?>
                    <th>Ações</th>
                <?php endif; ?>
            </tr>
            <?php foreach ($participacoes as $participacao): ?>
                <?php $anulada = $participacao['anulado_em'] !== null; ?>
                <tr>
                    <?php if ($podeAgir): ?>
                        <td><input type="checkbox" class="marcar-linha" name="participacao_ids[]" value="<?php echo (int) $participacao['id']; ?>" form="form-acoes-em-massa"></td>
                    <?php endif; ?>
                    <td>
                        <?php echo htmlspecialchars($participacao['participante_nome'], ENT_QUOTES, 'UTF-8'); ?>
                        <br><small><?php echo htmlspecialchars($participacao['participante_email'], ENT_QUOTES, 'UTF-8'); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($participacao['competicao_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars(formatarDataHora($participacao['participou_em']), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo (int) $participacao['pontos_creditados']; ?></td>
                    <td>
                        <?php if ($anulada): ?>
                            <span class="status-pill vermelho">Anulada</span>
                            <br><small>em <?php echo htmlspecialchars(formatarDataHora($participacao['anulado_em']), ENT_QUOTES, 'UTF-8'); ?><?php echo !empty($participacao['anulado_por_nome']) ? ' por ' . htmlspecialchars($participacao['anulado_por_nome'], ENT_QUOTES, 'UTF-8') : ''; ?></small>
                            <?php if (!empty($participacao['motivo_anulacao'])): ?>
                                <br><small>Motivo: <?php echo htmlspecialchars($participacao['motivo_anulacao'], ENT_QUOTES, 'UTF-8'); ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="status-pill verde">Válida</span>
                        <?php endif; ?>
                    </td>
                    <?php if ($podeAgir): ?>
                        <td>
                            <div class="acoes-icones">
                                <?php if ($anulada): ?>
                                    <form method="post" action="<?php echo url('competicoes/reverterAnulacao/' . (int) $evento['id'] . '/' . (int) $participacao['id']); ?>" onsubmit="return confirm('Desfazer a anulação e devolver os pontos?');"><?= campoCsrf() ?>
                                        <button type="submit" class="btn-icone" title="Desfazer a anulação">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <polyline points="1 4 1 10 7 10"></polyline>
                                                <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                                            </svg>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="<?php echo url('competicoes/anular/' . (int) $evento['id'] . '/' . (int) $participacao['id']); ?>" onsubmit="return confirm('Anular esta participação e retirar os pontos?');"><?= campoCsrf() ?>
                                        <input type="text" name="motivo" maxlength="500" required placeholder="Motivo, lido pelo participante" size="24">
                                        <button type="submit" class="btn-icone" title="Anular a participação">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                                <line x1="9" y1="9" x2="15" y2="15"></line>
                                            </svg>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <?php if ($podeAgir): ?>
        <p>Com as selecionadas:
            <label>Motivo da anulação
                <input type="text" name="motivo" maxlength="500" size="40" form="form-acoes-em-massa" placeholder="O participante lê este motivo">
            </label>
            <button type="submit" form="form-acoes-em-massa" formaction="<?php echo url('competicoes/anularEmLote/' . (int) $evento['id']); ?>"
                    onclick="return confirm('Anular as participações selecionadas?');">Anular</button>
            <button type="submit" form="form-acoes-em-massa" formaction="<?php echo url('competicoes/reverterAnulacaoEmLote/' . (int) $evento['id']); ?>"
                    onclick="return confirm('Desfazer a anulação das participações selecionadas?');">Desfazer anulação</button>
        </p>
    <?php endif; ?>
<?php endif; ?>
