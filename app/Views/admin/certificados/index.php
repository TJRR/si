<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 59: quem tem direito a certificado neste evento, com o que falta
 * emitir. Padrao de tabela do projeto (admin/usuarios.php,
 * admin/auditoria/index.php e admin/divulgacao/comprovacoes.php): barra de
 * filtros com botao de aplicar, linha de resumo com exportacao, tabela com
 * selecao por linha, operacao em lote e paginacao.
 *
 * A lista e' NOMINAL porque e' dela que sai a entrega do documento no balcao
 * e a conferencia de quem reclama que nao recebeu.
 *
 * A apuracao nao e' guardada: esta tela recalcula a cada abertura, e por isso
 * mostra a situacao de agora. O que fica congelado e' o certificado EMITIDO,
 * na outra aba.
 */
$parametrosFiltro = array_filter([
    'busca' => $filtros['busca'],
    'condicao' => $filtros['condicao'],
    'situacao' => $filtros['situacao'],
], function ($valor) {
    return $valor !== '' && $valor !== null;
});
$totalPaginas = (int) ceil($total / $porPagina);
$podeEmitir = $podeEditar && $eventoTerminou && (int) $config['ativo'] === 1;
$texto = new \App\Services\CertificadoTextoService();
?>
<div class="pagina-titulo-acoes">
    <h1>Certificados: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
</div>

<?php if ((int) $config['ativo'] !== 1): ?>
    <p class="status-pill laranja">
        O módulo de certificados está desligado para este evento. Ligue em
        <a href="<?php echo url('certificados/configuracoes/' . (int) $evento['id']); ?>">Configurações</a>.
    </p>
<?php elseif (!$eventoTerminou): ?>
    <p class="status-pill laranja">
        A emissão começa depois do último dia do evento
        (<?php echo formatarData($evento['data_fim']); ?>). Até lá a carga horária ainda pode mudar, e o
        documento é guardado exatamente como foi emitido.
    </p>
<?php elseif (!$emissaoAberta): ?>
    <p class="status-pill laranja">
        A emissão ainda não foi aberta ao participante: a organização pode emitir e imprimir, e quem
        participou só alcança o documento no aplicativo depois que a chave for aberta em
        <a href="<?php echo url('certificados/configuracoes/' . (int) $evento['id']); ?>">Configurações</a>.
    </p>
<?php endif; ?>

<?php if ($textosFaltando !== []): ?>
    <p class="status-pill vermelho">
        Sem texto escrito, estes certificados não podem ser emitidos:
        <?php echo htmlspecialchars(implode(', ', $textosFaltando), ENT_QUOTES, 'UTF-8'); ?>.
    </p>
<?php endif; ?>

<div class="admin-card">
    <p>
        <strong>Quem tem direito ao certificado do evento:</strong>
        <?php if ($exigencias === []): ?>
            nenhuma exigência foi escrita, então todo inscrito tem direito. Quem conduziu atividade e quem
            avaliou trabalho entram pela própria designação.
        <?php else: ?>
            quem cumpre <em>todas</em> as exigências escritas
            (<?php
            $frases = [];

            if (isset($exigencias['min_atividades'])) {
                $frases[] = $exigencias['min_atividades'] . ' atividade(s) diferente(s) com presença';
            }

            if (isset($exigencias['min_dias'])) {
                $frases[] = $exigencias['min_dias'] . ' dia(s) diferente(s)';
            }

            if (isset($exigencias['min_horas'])) {
                $frases[] = $exigencias['min_horas'] . ' hora(s) apurada(s)';
            }

            if (isset($exigencias['exige_credenciamento'])) {
                $frases[] = 'credenciamento no local';
            }

            echo htmlspecialchars(implode('; ', $frases), ENT_QUOTES, 'UTF-8');
            ?>). Quem conduziu atividade e quem avaliou trabalho entram pela própria designação, sem passar
            por essas exigências.
        <?php endif; ?>
    </p>
    <p><small>
        A carga horária soma a união dos horários das atividades: duas atividades no mesmo horário contam
        uma vez, então o número nunca passa do tempo real do evento.
    </small></p>
</div>

<div class="filtros-barra-wrapper">
    <form method="get" action="<?php echo config('base_path'); ?>/index.php" class="filtros-barra">
        <input type="hidden" name="r" value="certificados/index/<?php echo (int) $evento['id']; ?>">
        <label class="filtro-busca">Busca:
            <input type="text" name="busca" placeholder="Nome ou correio eletrônico" value="<?php echo htmlspecialchars($filtros['busca'], ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <label>Condição:
            <select name="condicao">
                <option value="">Todas</option>
                <?php foreach (\App\Services\CertificadoElegibilidadeService::ROTULO_CONDICAO as $chave => $rotulo): ?>
                    <option value="<?php echo htmlspecialchars($chave, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $filtros['condicao'] === $chave ? ' selected' : ''; ?>><?php echo htmlspecialchars(ucfirst($rotulo), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Situação:
            <select name="situacao">
                <option value="">Todas</option>
                <option value="pendentes"<?php echo $filtros['situacao'] === 'pendentes' ? ' selected' : ''; ?>>Falta emitir</option>
                <option value="completos"<?php echo $filtros['situacao'] === 'completos' ? ' selected' : ''; ?>>Tudo emitido</option>
            </select>
        </label>
        <div class="filtros-barra-acoes">
            <button type="submit" class="btn-icone" title="Filtrar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                </svg>
            </button>
            <a href="<?php echo url('certificados/index/' . (int) $evento['id']); ?>" class="btn-icone" title="Limpar filtros">
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
        <?php echo (int) $total === 1 ? 'pessoa com direito' : 'pessoas com direito'; ?>
    </span>
    <?php if ((int) $total > 0): ?>
        <a href="<?php echo htmlspecialchars(url('certificados/exportarElegiveis/' . (int) $evento['id']) . ($parametrosFiltro !== [] ? '&' . http_build_query($parametrosFiltro) : ''), ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Exportar a lista filtrada">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
        </a>
    <?php endif; ?>
</div>

<?php if ($dossies === []): ?>
    <p>Ninguém com direito a certificado<?php echo $parametrosFiltro !== [] ? ' com estes filtros' : ' neste evento até o momento'; ?>.</p>
<?php else: ?>
    <?php if ($podeEmitir): ?>
        <form method="post" id="form-acoes-em-massa" action="<?php echo url('certificados/emitirSelecionados/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
            <?php foreach ($parametrosFiltro as $campo => $valor): ?>
                <input type="hidden" name="<?php echo htmlspecialchars($campo, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8'); ?>">
            <?php endforeach; ?>
            <input type="hidden" name="pagina" value="<?php echo (int) $pagina; ?>">
        </form>
    <?php endif; ?>

    <div class="tabela-scroll">
        <table border="1" cellpadding="6">
            <tr>
                <?php if ($podeEmitir): ?>
                    <th><input type="checkbox" id="marcar-todos"></th>
                <?php endif; ?>
                <th>Nome</th>
                <th>Condição</th>
                <th>Atividades</th>
                <th>Dias</th>
                <th>Carga horária</th>
                <th>Documentos</th>
                <?php if ($podeEmitir): ?>
                    <th>Ações</th>
                <?php endif; ?>
            </tr>
            <?php foreach ($dossies as $chavePessoa => $dossie): ?>
                <?php $completo = $dossie['emitidos'] >= count($dossie['itens']); ?>
                <tr>
                    <?php if ($podeEmitir): ?>
                        <td><input type="checkbox" class="marcar-linha" name="chaves[]" value="<?php echo htmlspecialchars($chavePessoa, ENT_QUOTES, 'UTF-8'); ?>" form="form-acoes-em-massa"<?php echo $completo ? ' disabled' : ''; ?>></td>
                    <?php endif; ?>
                    <td>
                        <?php echo htmlspecialchars($dossie['nome'], ENT_QUOTES, 'UTF-8'); ?>
                        <?php if (!empty($dossie['email'])): ?>
                            <br><small><?php echo htmlspecialchars($dossie['email'], ENT_QUOTES, 'UTF-8'); ?></small>
                        <?php endif; ?>
                        <?php if ($dossie['usuario_id'] === null): ?>
                            <br><small>Coautor sem conta no sistema: o documento dele sai por aqui.</small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($texto->condicoesPorExtenso($dossie['condicoes']), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo (int) $dossie['atividades']; ?></td>
                    <td><?php echo (int) $dossie['dias']; ?></td>
                    <td><?php echo htmlspecialchars(\App\Services\CertificadoElegibilidadeService::formatarCargaHoraria($dossie['minutos_total']), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <?php echo (int) $dossie['emitidos']; ?> de <?php echo count($dossie['itens']); ?>
                        <?php if (!$completo): ?>
                            <span class="status-pill laranja">Falta emitir</span>
                        <?php endif; ?>
                        <br><small>
                            <?php
                            $rotulos = [];

                            foreach ($dossie['itens'] as $item) {
                                $rotulos[] = $item['rotulo'];
                            }

                            echo htmlspecialchars(implode('; ', $rotulos), ENT_QUOTES, 'UTF-8');
                            ?>
                        </small>
                    </td>
                    <?php if ($podeEmitir): ?>
                        <td>
                            <?php if (!$completo): ?>
                                <form method="post" action="<?php echo url('certificados/emitir/' . (int) $evento['id']); ?>" onsubmit="return confirm('Emitir os certificados desta pessoa? O documento fica guardado como foi emitido.');"><?= campoCsrf() ?>
                                    <input type="hidden" name="chave" value="<?php echo htmlspecialchars($chavePessoa, ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" class="btn-icone" title="Emitir">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                            <polyline points="14 2 14 8 20 8"></polyline>
                                            <polyline points="9 15 11 17 15 13"></polyline>
                                        </svg>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <?php if ($podeEmitir): ?>
        <p>Com as selecionadas:</p>
        <p>
            <button type="submit" form="form-acoes-em-massa"
                    onclick="return confirm('Emitir os certificados das pessoas selecionadas?');">Emitir</button>
            <small>Até <?php echo (int) $tetoLote; ?> documentos por vez. Acima disso, repita a operação.</small>
        </p>
    <?php endif; ?>

    <?php if ($totalPaginas > 1): ?>
        <p class="auditoria-paginacao">
            <span>Página <?php echo (int) $pagina; ?> de <?php echo $totalPaginas; ?></span>
            <?php if ((int) $pagina > 1): ?>
                <a href="<?php echo htmlspecialchars(url('certificados/index/' . (int) $evento['id']) . '&' . http_build_query($parametrosFiltro + ['pagina' => $pagina - 1]), ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Página anterior">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </a>
            <?php endif; ?>
            <?php if ((int) $pagina < $totalPaginas): ?>
                <a href="<?php echo htmlspecialchars(url('certificados/index/' . (int) $evento['id']) . '&' . http_build_query($parametrosFiltro + ['pagina' => $pagina + 1]), ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Próxima página">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </a>
            <?php endif; ?>
        </p>
    <?php endif; ?>
<?php endif; ?>
