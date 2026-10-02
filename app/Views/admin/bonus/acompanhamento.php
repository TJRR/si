<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Acompanhamento dos bônus: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <?php if ($podeEditar && empty($gincanaEncerrada)): ?>
        <form method="post" action="<?php echo url('bonus/reconferir/' . (int) $evento['id']); ?>" style="display:inline;" onsubmit="return confirm('Reconferir os bônus de todos os inscritos agora?');"><?= campoCsrf() ?>
            <button type="submit" class="btn-acao">Reconferir agora</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($gincanaEncerrada)): ?>
    <p class="status-pill laranja">A gincana deste evento foi encerrada e a classificação está congelada: nenhum crédito pode ser anulado, restabelecido ou reconferido.</p>
<?php endif; ?>

<div class="admin-card">
    <p>
        <strong><?php echo (int) $numeros['validos']; ?></strong> créditos válidos ·
        <strong><?php echo (int) $numeros['pontos']; ?></strong> pontos concedidos ·
        <strong><?php echo (int) $numeros['pessoas']; ?></strong> pessoas alcançadas ·
        <strong><?php echo (int) $numeros['anulados']; ?></strong> anulados
    </p>
</div>

<p style="color:#555;font-size:0.9em;">
    Anular um crédito aqui tira os pontos, avisa a pessoa e é definitivo: nenhuma presença nova o concede
    de novo, e o único caminho de volta é desfazer a anulação. Diferente disso é o crédito
    <strong>anulado pelo sistema</strong>, que aparece quando a organização remove uma presença e o bônus
    perde a quantidade exigida: esse volta sozinho se a presença for registrada outra vez.
</p>

<?php
$parametrosFiltro = array_filter([
    'busca' => $filtros['busca'],
    'bonus_id' => $filtros['bonus_id'] > 0 ? $filtros['bonus_id'] : '',
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
        <input type="hidden" name="r" value="bonus/acompanhamento/<?php echo (int) $evento['id']; ?>">
        <label class="filtro-busca">Busca:
            <input type="text" name="busca" placeholder="Nome ou correio eletrônico" value="<?php echo htmlspecialchars($filtros['busca'], ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <?php if (count($bonus) > 1): ?>
            <label>Bônus:
                <select name="bonus_id">
                    <option value="">Todos</option>
                    <?php foreach ($bonus as $item): ?>
                        <option value="<?php echo (int) $item['id']; ?>"<?php echo (int) $filtros['bonus_id'] === (int) $item['id'] ? ' selected' : ''; ?>><?php echo htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <label>Situação:
            <select name="situacao">
                <option value="">Todas</option>
                <option value="validos"<?php echo $filtros['situacao'] === 'validos' ? ' selected' : ''; ?>>Válidos</option>
                <option value="anulados"<?php echo $filtros['situacao'] === 'anulados' ? ' selected' : ''; ?>>Anulados</option>
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
            <a href="<?php echo url('bonus/acompanhamento/' . (int) $evento['id']); ?>" class="btn-icone" title="Limpar filtros">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="1 4 1 10 7 10"></polyline>
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                </svg>
            </a>
        </div>
    </form>
</div>

<?php if ($podeEditar && empty($gincanaEncerrada) && !empty($bonus)): ?>
<div class="admin-card">
    <p><strong>Cancelar todos os créditos de um bônus</strong></p>
    <p style="color:#555;font-size:0.9em;">
        Use quando um bônus foi cadastrado errado e concedeu pontos a quem não devia. Cada pessoa atingida
        recebe um aviso no aplicativo, então com muitos créditos a operação demora alguns segundos.
        Para confirmar, digite o nome exato do bônus.
    </p>
    <?php foreach ($bonus as $item): ?>
    <?php if ((int) $item['total_creditos'] === 0) { continue; } ?>
    <form method="post" action="<?php echo url('bonus/anularEmLote/' . (int) $evento['id'] . '/' . (int) $item['id']); ?>" onsubmit="return confirm('Cancelar todos os créditos válidos deste bônus?');"><?= campoCsrf() ?>
        <p>
            <strong><?php echo htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
            (<?php echo (int) $item['total_creditos']; ?> <?php echo (int) $item['total_creditos'] === 1 ? 'crédito válido' : 'créditos válidos'; ?>)
        </p>
        <label>Motivo:
            <input type="text" name="motivo" maxlength="500" size="40" required>
        </label>
        <label>Digite o nome do bônus:
            <input type="text" name="confirmacao" size="30" placeholder="<?php echo htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </label>
        <button type="submit">Cancelar todos</button>
    </form>
    <form method="post" action="<?php echo url('bonus/reverterAnulacaoEmLote/' . (int) $evento['id'] . '/' . (int) $item['id']); ?>" onsubmit="return confirm('Restabelecer os créditos que o Administrador cancelou neste bônus?');"><?= campoCsrf() ?>
        <button type="submit">Desfazer o cancelamento deste bônus</button>
    </form>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="auditoria-resumo">
    <span>
        <?php echo count($creditos); ?>
        <?php echo count($creditos) === 1 ? 'crédito encontrado' : 'créditos encontrados'; ?>
    </span>
    <?php if ($podeEditar && !empty($creditos)): ?>
        <a href="<?php echo htmlspecialchars(url('bonus/acompanhamentoExportar/' . (int) $evento['id']) . ($parametrosFiltro !== [] ? '&' . http_build_query($parametrosFiltro) : ''), ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Exportar a lista filtrada">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
        </a>
    <?php endif; ?>
</div>

<?php if (empty($creditos)): ?>
    <p>Nenhum crédito encontrado<?php echo $parametrosFiltro !== [] ? ' com estes filtros' : ' neste evento ainda'; ?>.</p>
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
                <th>Bônus</th>
                <th>Exigência atingida</th>
                <th>Creditado em</th>
                <th>Pontos</th>
                <th>Situação</th>
                <?php if ($podeAgir): ?>
                    <th>Ações</th>
                <?php endif; ?>
            </tr>
            <?php foreach ($creditos as $credito): ?>
                <?php
                $anulado = $credito['anulado_em'] !== null;
                $peloSistema = $anulado && $credito['anulado_por'] === null;
                ?>
                <tr>
                    <?php if ($podeAgir): ?>
                        <td><input type="checkbox" class="marcar-linha" name="credito_ids[]" value="<?php echo (int) $credito['id']; ?>" form="form-acoes-em-massa"></td>
                    <?php endif; ?>
                    <td>
                        <?php echo htmlspecialchars($credito['participante_nome'], ENT_QUOTES, 'UTF-8'); ?>
                        <br><small><?php echo htmlspecialchars($credito['participante_email'], ENT_QUOTES, 'UTF-8'); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($credito['bonus_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo (int) $credito['exigencia_atingida']; ?></td>
                    <td><?php echo htmlspecialchars(formatarDataHora($credito['creditado_em']), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo (int) $credito['pontos_creditados']; ?></td>
                    <td>
                        <?php if ($peloSistema): ?>
                            <span class="status-pill laranja">Anulado pelo sistema</span>
                            <br><small>Volta sozinho se a presença for registrada outra vez.</small>
                        <?php elseif ($anulado): ?>
                            <span class="status-pill vermelho">Anulado</span>
                            <br><small>em <?php echo htmlspecialchars(formatarDataHora($credito['anulado_em']), ENT_QUOTES, 'UTF-8'); ?><?php echo !empty($credito['anulado_por_nome']) ? ' por ' . htmlspecialchars($credito['anulado_por_nome'], ENT_QUOTES, 'UTF-8') : ''; ?></small>
                        <?php else: ?>
                            <span class="status-pill verde">Válido</span>
                        <?php endif; ?>
                        <?php if ($anulado && !empty($credito['motivo_anulacao'])): ?>
                            <br><small>Motivo: <?php echo htmlspecialchars($credito['motivo_anulacao'], ENT_QUOTES, 'UTF-8'); ?></small>
                        <?php endif; ?>
                    </td>
                    <?php if ($podeAgir): ?>
                        <td>
                            <div class="acoes-icones">
                                <?php if ($peloSistema): ?>
                                    <small>Sem ação: volta sozinho</small>
                                <?php elseif ($anulado): ?>
                                    <form method="post" action="<?php echo url('bonus/reverterAnulacao/' . (int) $evento['id'] . '/' . (int) $credito['id']); ?>" onsubmit="return confirm('Desfazer a anulação e devolver os pontos?');"><?= campoCsrf() ?>
                                        <button type="submit" class="btn-icone" title="Desfazer a anulação">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <polyline points="1 4 1 10 7 10"></polyline>
                                                <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                                            </svg>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="<?php echo url('bonus/anular/' . (int) $evento['id'] . '/' . (int) $credito['id']); ?>" onsubmit="return confirm('Anular este crédito e retirar os pontos?');"><?= campoCsrf() ?>
                                        <input type="text" name="motivo" maxlength="500" required placeholder="Motivo, lido pelo participante" size="24">
                                        <button type="submit" class="btn-icone" title="Anular o crédito">
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
        <p>Com os selecionados:
            <label>Motivo da anulação
                <input type="text" name="motivo" maxlength="500" size="40" form="form-acoes-em-massa" placeholder="O participante lê este motivo">
            </label>
            <button type="submit" form="form-acoes-em-massa" formaction="<?php echo url('bonus/anularSelecionadosEmLote/' . (int) $evento['id']); ?>"
                    onclick="return confirm('Anular os créditos selecionados?');">Anular</button>
            <button type="submit" form="form-acoes-em-massa" formaction="<?php echo url('bonus/reverterSelecionadosEmLote/' . (int) $evento['id']); ?>"
                    onclick="return confirm('Desfazer a anulação dos créditos selecionados?');">Desfazer anulação</button>
        </p>
        <p style="color:#555;font-size:0.9em;">
            "Desfazer anulação" alcança só o que uma pessoa anulou. O crédito anulado pelo sistema volta sozinho
            quando a presença que faltava for registrada outra vez.
        </p>
    <?php endif; ?>
<?php endif; ?>
