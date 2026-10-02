<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 59: os certificados ja' emitidos, com baixa e cancelamento. Mesmo
 * padrao de tabela da aba anterior.
 *
 * Todo dado mostrado aqui vem da PROPRIA LINHA do certificado, nunca de uma
 * apuracao refeita: e' o nome, a condicao e a carga horaria que foram
 * impressos, e e' com eles que a organizacao confere o documento que a pessoa
 * apresenta.
 *
 * O cancelamento nao apaga o arquivo: o documento pode ja' ter sido juntado a
 * um processo, e apagar tornaria irreproduzivel o que foi entregue. O que
 * muda e' que ele deixa de ser servido e a pagina publica passa a informar o
 * cancelamento.
 */
$parametrosFiltro = array_filter([
    'busca' => $filtros['busca'],
    'tipo' => $filtros['tipo'],
    'condicao' => $filtros['condicao'],
    'situacao' => $filtros['situacao'],
], function ($valor) {
    return $valor !== '' && $valor !== null;
});
$totalPaginas = (int) ceil($total / $porPagina);
$tipos = [
    \App\Services\CertificadoElegibilidadeService::TIPO_EVENTO => 'Participação no evento',
    \App\Services\CertificadoElegibilidadeService::TIPO_ATIVIDADE => 'Atividade',
    \App\Services\CertificadoElegibilidadeService::TIPO_APRESENTACAO => 'Apresentação de trabalho',
];
?>
<div class="pagina-titulo-acoes">
    <h1>Certificados emitidos: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
</div>

<?php if (!$podeEditar): ?>
    <p class="status-pill laranja">Somente leitura: só o Administrador cancela certificado.</p>
<?php endif; ?>

<div class="admin-card">
    <p>
        <strong>Página pública de conferência:</strong>
        <a href="<?php echo url('certificadoPublico/index'); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars(urlAbsoluta('certificadoPublico/index'), ENT_QUOTES, 'UTF-8'); ?></a>
    </p>
    <p><small>
        É o endereço impresso em todo certificado. Quem recebe um documento digita ali o código e confere se
        ele foi emitido por este sistema. O código de cada linha abaixo leva direto à conferência dele.
    </small></p>
</div>

<?php if ($resumo !== []): ?>
    <div class="admin-card">
        <p>
            <?php foreach ($tipos as $chave => $rotulo): ?>
                <?php if (isset($resumo[$chave])): ?>
                    <strong><?php echo (int) $resumo[$chave]['total']; ?></strong>
                    <?php echo htmlspecialchars(mb_strtolower($rotulo, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>
                    <?php if ((int) $resumo[$chave]['cancelados'] > 0): ?>
                        (<?php echo (int) $resumo[$chave]['cancelados']; ?> cancelado(s))
                    <?php endif; ?>
                    <br>
                <?php endif; ?>
            <?php endforeach; ?>
        </p>
    </div>
<?php endif; ?>

<div class="filtros-barra-wrapper">
    <form method="get" action="<?php echo config('base_path'); ?>/index.php" class="filtros-barra">
        <input type="hidden" name="r" value="certificados/emitidos/<?php echo (int) $evento['id']; ?>">
        <label class="filtro-busca">Busca:
            <input type="text" name="busca" placeholder="Nome, código ou correio eletrônico" value="<?php echo htmlspecialchars($filtros['busca'], ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <label>Tipo:
            <select name="tipo">
                <option value="">Todos</option>
                <?php foreach ($tipos as $chave => $rotulo): ?>
                    <option value="<?php echo htmlspecialchars($chave, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $filtros['tipo'] === $chave ? ' selected' : ''; ?>><?php echo htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Situação:
            <select name="situacao">
                <option value="">Todas</option>
                <option value="validos"<?php echo $filtros['situacao'] === 'validos' ? ' selected' : ''; ?>>Válidos</option>
                <option value="cancelados"<?php echo $filtros['situacao'] === 'cancelados' ? ' selected' : ''; ?>>Cancelados</option>
            </select>
        </label>
        <div class="filtros-barra-acoes">
            <button type="submit" class="btn-icone" title="Filtrar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                </svg>
            </button>
            <a href="<?php echo url('certificados/emitidos/' . (int) $evento['id']); ?>" class="btn-icone" title="Limpar filtros">
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
        <?php echo (int) $total; ?>
        <?php echo (int) $total === 1 ? 'certificado encontrado' : 'certificados encontrados'; ?>
    </span>
    <?php if ((int) $total > 0): ?>
        <a href="<?php echo htmlspecialchars(url('certificados/exportarEmitidos/' . (int) $evento['id']) . ($parametrosFiltro !== [] ? '&' . http_build_query($parametrosFiltro) : ''), ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Exportar a lista filtrada">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
        </a>
    <?php endif; ?>
</div>

<?php if (empty($certificados)): ?>
    <p>Nenhum certificado emitido<?php echo $parametrosFiltro !== [] ? ' com estes filtros' : ' neste evento até o momento'; ?>.</p>
<?php else: ?>
    <form method="post" id="form-acoes-em-massa"><?= campoCsrf() ?>
        <?php foreach ($parametrosFiltro as $campo => $valor): ?>
            <input type="hidden" name="<?php echo htmlspecialchars($campo, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8'); ?>">
        <?php endforeach; ?>
        <input type="hidden" name="pagina" value="<?php echo (int) $pagina; ?>">
    </form>

    <div class="tabela-scroll">
        <table border="1" cellpadding="6">
            <tr>
                <th><input type="checkbox" id="marcar-todos"></th>
                <th>Nome</th>
                <th>Tipo</th>
                <th>Referência</th>
                <th>Condição</th>
                <th>Carga horária</th>
                <th>Código</th>
                <th>Emitido em</th>
                <th>Situação</th>
                <th>Ações</th>
            </tr>
            <?php foreach ($certificados as $certificado): ?>
                <?php $cancelado = $certificado['cancelado_em'] !== null; ?>
                <tr>
                    <td><input type="checkbox" class="marcar-linha" name="ids[]" value="<?php echo (int) $certificado['id']; ?>" form="form-acoes-em-massa"></td>
                    <td>
                        <?php echo htmlspecialchars($certificado['nome'], ENT_QUOTES, 'UTF-8'); ?>
                        <?php if (!empty($certificado['usuario_email'])): ?>
                            <br><small><?php echo htmlspecialchars($certificado['usuario_email'], ENT_QUOTES, 'UTF-8'); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars(isset($tipos[$certificado['tipo']]) ? $tipos[$certificado['tipo']] : $certificado['tipo'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <?php
                        $referencia = $certificado['atividade_nome'] !== null
                            ? $certificado['atividade_nome']
                            : (string) $certificado['trabalho_titulo'];
                        echo htmlspecialchars($referencia, ENT_QUOTES, 'UTF-8');
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($certificado['condicoes'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <?php echo $certificado['carga_horaria_minutos'] !== null
                            ? htmlspecialchars(\App\Services\CertificadoElegibilidadeService::formatarCargaHoraria($certificado['carga_horaria_minutos']), ENT_QUOTES, 'UTF-8')
                            : ''; ?>
                    </td>
                    <td>
                        <a href="<?php echo htmlspecialchars(url('certificadoPublico/index') . '&codigo=' . urlencode($certificado['codigo_verificacao']), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" title="Conferir este certificado na página pública"><?php echo htmlspecialchars($certificado['codigo_verificacao'], ENT_QUOTES, 'UTF-8'); ?></a>
                    </td>
                    <td><?php echo formatarDataHora($certificado['emitido_em']); ?></td>
                    <td>
                        <?php if ($cancelado): ?>
                            <span class="status-pill vermelho">Cancelado</span>
                            <br><small>
                                <?php echo formatarData($certificado['cancelado_em']); ?>
                                <?php if (!empty($certificado['cancelado_por_nome'])): ?>
                                    por <?php echo htmlspecialchars($certificado['cancelado_por_nome'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php endif; ?>
                                <?php if (!empty($certificado['motivo_cancelamento'])): ?>
                                    <br><?php echo htmlspecialchars($certificado['motivo_cancelamento'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php endif; ?>
                            </small>
                        <?php else: ?>
                            <span class="status-pill verde">Válido</span>
                            <?php if ($certificado['emitido_por'] === null): ?>
                                <br><small>Retirado pelo próprio interessado</small>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="acoes-icones">
                            <?php if (!$cancelado): ?>
                                <a href="<?php echo url('certificados/baixar/' . (int) $evento['id'] . '/' . (int) $certificado['id']); ?>" class="btn-icone" title="Abrir o certificado" target="_blank" rel="noopener">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="7 10 12 15 17 10"></polyline>
                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                    </svg>
                                </a>
                                <?php if ($podeEditar): ?>
                                    <form method="post" action="<?php echo url('certificados/cancelar/' . (int) $evento['id'] . '/' . (int) $certificado['id']); ?>" onsubmit="return confirm('Cancelar este certificado? Ele deixa de ser entregue e a página de conferência passa a informar o cancelamento.');"><?= campoCsrf() ?>
                                        <input type="text" name="motivo" maxlength="500" required placeholder="Motivo do cancelamento" size="24">
                                        <button type="submit" class="btn-icone" title="Cancelar">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                                <line x1="9" y1="9" x2="15" y2="15"></line>
                                            </svg>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <p>Com os selecionados:</p>
    <p>
        <button type="submit" form="form-acoes-em-massa" formaction="<?php echo url('certificados/baixarSelecionados/' . (int) $evento['id']); ?>">Baixar num arquivo único</button>
        <?php if ($podeEditar): ?>
            <label>Motivo do cancelamento
                <input type="text" name="motivo" maxlength="500" size="40" form="form-acoes-em-massa">
            </label>
            <button type="submit" form="form-acoes-em-massa" formaction="<?php echo url('certificados/cancelarSelecionados/' . (int) $evento['id']); ?>"
                    onclick="return confirm('Cancelar os certificados selecionados?');">Cancelar</button>
        <?php endif; ?>
        <br><small>Até <?php echo (int) $tetoLote; ?> documentos por arquivo. Certificado cancelado não entra.</small>
    </p>

    <?php if ($totalPaginas > 1): ?>
        <p class="auditoria-paginacao">
            <span>Página <?php echo (int) $pagina; ?> de <?php echo $totalPaginas; ?></span>
            <?php if ((int) $pagina > 1): ?>
                <a href="<?php echo htmlspecialchars(url('certificados/emitidos/' . (int) $evento['id']) . '&' . http_build_query($parametrosFiltro + ['pagina' => $pagina - 1]), ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Página anterior">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </a>
            <?php endif; ?>
            <?php if ((int) $pagina < $totalPaginas): ?>
                <a href="<?php echo htmlspecialchars(url('certificados/emitidos/' . (int) $evento['id']) . '&' . http_build_query($parametrosFiltro + ['pagina' => $pagina + 1]), ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Próxima página">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </a>
            <?php endif; ?>
        </p>
    <?php endif; ?>
<?php endif; ?>
