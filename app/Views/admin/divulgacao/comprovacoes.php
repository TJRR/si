<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 58: tela de trabalho da auditoria de Divulgacao, no padrao de tabela
 * do projeto (admin/usuarios.php e admin/auditoria/index.php): barra de
 * filtros com botao de aplicar, linha de resumo com exportacao, tabela com
 * selecao por linha, operacoes em lote e paginacao.
 *
 * A lista e' NOMINAL, ao contrario da de Conexoes ate' esta fase: a
 * conferencia por amostragem prevista no documento da dinamica de pontos
 * depende de comparar a prova com a conta que a pessoa cadastrou em "Meu
 * Perfil".
 *
 * A imagem so' abre para o Administrador ($podeEditar): e' o dado de
 * terceiro nao consentido deste modulo. O endereco da publicacao, que e'
 * conteudo publico, aparece para os dois perfis, revalidado por
 * linkHttpValido(), escapado, em aba nova e sem repassar a origem.
 *
 * O formulario de lote fica VAZIO e fora da tabela, e as caixas de cada
 * linha se ligam a ele pelo atributo "form=": HTML nao aceita formulario
 * dentro de formulario, e sem isso os botoes de cada linha submeteriam o
 * formulario errado. Mesma saida da tela de homologacao de inscritos.
 */
$parametrosFiltro = array_filter([
    'busca' => $filtros['busca'],
    'rede' => $filtros['rede'],
    'tipo_acao' => $filtros['tipo_acao'],
    'situacao' => $filtros['situacao'],
    'data_inicio' => $filtros['data_inicio'],
    'data_fim' => $filtros['data_fim'],
], function ($valor) {
    return $valor !== '' && $valor !== null;
});
$podeAgir = $podeEditar && empty($gincanaEncerrada);
$vendoExcluidas = $filtros['situacao'] === 'excluidas';
?>
<div class="pagina-titulo-acoes">
    <h1>Comprovações: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('divulgacao/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<?php if (!empty($gincanaEncerrada)): ?>
    <p class="status-pill laranja">A gincana deste evento foi encerrada e a classificação está congelada: nenhuma pontuação pode ser anulada, restabelecida ou excluída.</p>
<?php elseif (!$podeEditar): ?>
    <p class="status-pill laranja">Somente leitura: só o Administrador altera as comprovações.</p>
<?php endif; ?>

<div class="filtros-barra-wrapper">
    <form method="get" action="<?php echo config('base_path'); ?>/index.php" class="filtros-barra">
        <input type="hidden" name="r" value="divulgacao/comprovacoes/<?php echo (int) $evento['id']; ?>">
        <label class="filtro-busca">Busca:
            <input type="text" name="busca" placeholder="Nome ou correio eletrônico" value="<?php echo htmlspecialchars($filtros['busca'], ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <?php /* As opções de rede e de ação saem do que o evento recebeu, não da
        lista fechada: filtro que oferece o que ninguém enviou só atrapalha. */ ?>
        <?php if (count($redesComEnvio) > 1): ?>
            <label>Rede social:
                <select name="rede">
                    <option value="">Todas</option>
                    <?php foreach ($redesComEnvio as $chave => $rotulo): ?>
                        <option value="<?php echo htmlspecialchars($chave, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $filtros['rede'] === $chave ? ' selected' : ''; ?>><?php echo htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <?php if (count($acoesComEnvio) > 1): ?>
            <label>Ação:
                <select name="tipo_acao">
                    <option value="">Todas</option>
                    <?php foreach ($acoesComEnvio as $chave => $rotulo): ?>
                        <option value="<?php echo htmlspecialchars($chave, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $filtros['tipo_acao'] === $chave ? ' selected' : ''; ?>><?php echo htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <label>Situação:
            <select name="situacao">
                <option value="">Todas em pé</option>
                <option value="validas"<?php echo $filtros['situacao'] === 'validas' ? ' selected' : ''; ?>>Válidas</option>
                <option value="anuladas"<?php echo $filtros['situacao'] === 'anuladas' ? ' selected' : ''; ?>>Anuladas</option>
                <option value="excluidas"<?php echo $vendoExcluidas ? ' selected' : ''; ?>>Excluídas</option>
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
            <a href="<?php echo url('divulgacao/comprovacoes/' . (int) $evento['id']); ?>" class="btn-icone" title="Limpar filtros">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="1 4 1 10 7 10"></polyline>
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                </svg>
            </a>
        </div>
    </form>
</div>

<?php if ($podeEditar && (int) $totalImagens > 0): ?>
    <?php /* Limpeza de todas as imagens guardadas. As duas travas são as mesmas
    de antes: o evento precisa ter passado da própria data final, e o nome é
    redigitado. Para apagar só algumas, marque as linhas e use a operação em
    lote abaixo da tabela. */ ?>
    <div class="admin-card">
        <p>
            <strong><?php echo (int) $totalImagens; ?></strong>
            <?php echo (int) $totalImagens === 1 ? 'imagem guardada' : 'imagens guardadas'; ?>
            em área restrita. Elas podem conter dados de outras pessoas: depois do evento, apague-as. As
            comprovações, os pontos e a proteção contra reenvio da mesma imagem continuam funcionando, e a ação
            não pode ser desfeita.
        </p>
        <?php if (!$eventoEncerrado): ?>
            <p class="status-pill laranja">Apagar todas fica disponível depois que o evento passar da própria data final.</p>
        <?php else: ?>
            <form method="post" action="<?php echo url('divulgacao/expurgarImagens/' . (int) $evento['id']); ?>" onsubmit="return confirm('Apagar definitivamente todas as imagens de comprovação deste evento?');"><?= campoCsrf() ?>
                <label>Digite o nome do evento para confirmar
                    <input type="text" name="confirmacao" required placeholder="<?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?>">
                </label>
                <button type="submit">Apagar todas as imagens</button>
            </form>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="auditoria-resumo">
    <span>
        <?php echo (int) $total; ?>
        <?php echo (int) $total === 1 ? 'comprovação encontrada' : 'comprovações encontradas'; ?>
    </span>
    <?php if ((int) $total > 0): ?>
        <a href="<?php echo htmlspecialchars(url('divulgacao/exportar/' . (int) $evento['id']) . ($parametrosFiltro !== [] ? '&' . http_build_query($parametrosFiltro) : ''), ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Exportar a lista filtrada">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
        </a>
    <?php endif; ?>
</div>

<?php if (empty($comprovacoes)): ?>
    <p>Nenhuma comprovação encontrada<?php echo $parametrosFiltro !== [] ? ' com estes filtros' : ' neste evento até o momento'; ?>.</p>
<?php else: ?>
    <?php if ($podeAgir): ?>
        <form method="post" id="form-acoes-em-massa"><?= campoCsrf() ?>
            <input type="hidden" name="evento_id" value="<?php echo (int) $evento['id']; ?>">
            <?php /* O filtro e a página viajam no formulário para que a tela volte
            exatamente onde estava depois da operação. */ ?>
            <?php foreach ($parametrosFiltro as $campo => $valor): ?>
                <input type="hidden" name="<?php echo htmlspecialchars($campo, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8'); ?>">
            <?php endforeach; ?>
            <input type="hidden" name="pagina" value="<?php echo (int) $pagina; ?>">
        </form>
    <?php endif; ?>

    <div class="tabela-scroll">
        <table border="1" cellpadding="6">
            <tr>
                <?php if ($podeAgir): ?>
                    <th><input type="checkbox" id="marcar-todos"></th>
                <?php endif; ?>
                <th>Participante</th>
                <th>Rede</th>
                <th>Ação</th>
                <th>Enviado em</th>
                <th>Prova</th>
                <th>Pontos</th>
                <th>Situação</th>
                <?php if ($podeAgir): ?>
                    <th>Ações</th>
                <?php endif; ?>
            </tr>
            <?php foreach ($comprovacoes as $comprovacao): ?>
                <?php
                $anulada = $comprovacao['anulado_em'] !== null;
                $excluida = $comprovacao['excluido_em'] !== null;
                $repetida = !empty($comprovacao['arquivo_sha256']) && isset($resumosRepetidos[$comprovacao['arquivo_sha256']]);
                $redesDaPessoa = [];

                if (!empty($comprovacao['participante_redes'])) {
                    $decodificado = json_decode($comprovacao['participante_redes'], true);
                    $redesDaPessoa = is_array($decodificado) ? $decodificado : [];
                }
                ?>
                <tr>
                    <?php if ($podeAgir): ?>
                        <td><input type="checkbox" class="marcar-linha" name="comprovacao_ids[]" value="<?php echo (int) $comprovacao['id']; ?>" form="form-acoes-em-massa"></td>
                    <?php endif; ?>
                    <td>
                        <?php echo htmlspecialchars($comprovacao['participante_nome'], ENT_QUOTES, 'UTF-8'); ?>
                        <br><small><?php echo htmlspecialchars($comprovacao['participante_email'], ENT_QUOTES, 'UTF-8'); ?></small>
                        <?php if (isset($redesDaPessoa[$comprovacao['rede']]) && linkHttpValido($redesDaPessoa[$comprovacao['rede']])): ?>
                            <br><small>Conta cadastrada: <a href="<?php echo htmlspecialchars($redesDaPessoa[$comprovacao['rede']], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($redesDaPessoa[$comprovacao['rede']], ENT_QUOTES, 'UTF-8'); ?></a></small>
                        <?php elseif ($comprovacao['rede'] === 'whatsapp' && !empty($comprovacao['participante_telefone'])): ?>
                            <br><small>Telefone cadastrado: <?php echo htmlspecialchars((string) $comprovacao['participante_telefone'], ENT_QUOTES, 'UTF-8'); ?>
                            <?php echo !empty($comprovacao['participante_telefone_whatsapp']) ? '(marcado como WhatsApp)' : '(não marcado como WhatsApp)'; ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars(isset($rotulosRede[$comprovacao['rede']]) ? $rotulosRede[$comprovacao['rede']] : $comprovacao['rede'], ENT_QUOTES, 'UTF-8'); ?>
                        <?php if (!empty($comprovacao['rede_informada'])): ?>
                            <br><small><?php echo htmlspecialchars($comprovacao['rede_informada'], ENT_QUOTES, 'UTF-8'); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $comprovacao['tipo_acao'] === 'acompanhar' ? 'Passou a seguir' : 'Publicação'; ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($comprovacao['enviado_em'])); ?></td>
                    <td>
                        <div class="acoes-icones">
                            <?php if (!empty($comprovacao['endereco']) && linkHttpValido($comprovacao['endereco'])): ?>
                                <a href="<?php echo htmlspecialchars($comprovacao['endereco'], ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Abrir a publicação" target="_blank" rel="noopener noreferrer">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                        <polyline points="15 3 21 3 21 9"></polyline>
                                        <line x1="10" y1="14" x2="21" y2="3"></line>
                                    </svg>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($comprovacao['arquivo_path']) && $podeEditar): ?>
                                <a href="<?php echo url('divulgacao/imagem/' . (int) $evento['id'] . '/' . (int) $comprovacao['id']); ?>" class="btn-icone" title="Ver a imagem enviada" target="_blank" rel="noopener noreferrer">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                        <polyline points="21 15 16 10 5 21"></polyline>
                                    </svg>
                                </a>
                            <?php elseif (!empty($comprovacao['arquivo_path'])): ?>
                                <small>Só o Administrador abre</small>
                            <?php elseif ($comprovacao['arquivo_removido_em'] !== null): ?>
                                <small>Imagem apagada em <?php echo date('d/m/Y', strtotime($comprovacao['arquivo_removido_em'])); ?></small>
                            <?php endif; ?>
                        </div>
                        <?php if ($repetida): ?>
                            <span class="status-pill roxo">Mesma imagem em <?php echo (int) $resumosRepetidos[$comprovacao['arquivo_sha256']]; ?> participantes</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo (int) $comprovacao['pontos_creditados']; ?></td>
                    <td>
                        <?php if ($excluida): ?>
                            <span class="status-pill vermelho">Excluída</span>
                            <br><small>em <?php echo date('d/m/Y H:i', strtotime($comprovacao['excluido_em'])); ?><?php echo !empty($comprovacao['excluido_por_nome']) ? ' por ' . htmlspecialchars($comprovacao['excluido_por_nome'], ENT_QUOTES, 'UTF-8') : ''; ?></small>
                        <?php elseif ($anulada): ?>
                            <span class="status-pill vermelho">Anulada</span>
                            <br><small>em <?php echo date('d/m/Y H:i', strtotime($comprovacao['anulado_em'])); ?><?php echo !empty($comprovacao['anulado_por_nome']) ? ' por ' . htmlspecialchars($comprovacao['anulado_por_nome'], ENT_QUOTES, 'UTF-8') : ''; ?></small>
                            <?php if (!empty($comprovacao['motivo_anulacao'])): ?>
                                <br><small>Motivo: <?php echo htmlspecialchars($comprovacao['motivo_anulacao'], ENT_QUOTES, 'UTF-8'); ?></small>
                            <?php endif; ?>
                        <?php elseif ((int) $comprovacao['pontos_creditados'] > 0): ?>
                            <span class="status-pill verde">Válida</span>
                        <?php else: ?>
                            <span class="status-pill laranja">Sem pontos</span>
                        <?php endif; ?>
                    </td>
                    <?php if ($podeAgir): ?>
                        <td>
                            <div class="acoes-icones">
                                <?php if ($excluida): ?>
                                    <form method="post" action="<?php echo url('divulgacao/restaurarEmLote/' . (int) $evento['id']); ?>" onsubmit="return confirm('Restaurar esta comprovação?');"><?= campoCsrf() ?>
                                        <input type="hidden" name="comprovacao_ids[]" value="<?php echo (int) $comprovacao['id']; ?>">
                                        <button type="submit" class="btn-icone" title="Restaurar">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <polyline points="1 4 1 10 7 10"></polyline>
                                                <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                                            </svg>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <?php if ($anulada): ?>
                                        <form method="post" action="<?php echo url('divulgacao/reverterAnulacao/' . (int) $evento['id'] . '/' . (int) $comprovacao['id']); ?>" onsubmit="return confirm('Desfazer a anulação e devolver os pontos?');"><?= campoCsrf() ?>
                                            <button type="submit" class="btn-icone" title="Desfazer a anulação">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <polyline points="1 4 1 10 7 10"></polyline>
                                                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="<?php echo url('divulgacao/anular/' . (int) $evento['id'] . '/' . (int) $comprovacao['id']); ?>" onsubmit="return confirm('Anular a pontuação desta comprovação?');"><?= campoCsrf() ?>
                                            <input type="text" name="motivo" maxlength="500" required placeholder="Motivo, lido pelo participante" size="24">
                                            <button type="submit" class="btn-icone" title="Anular a pontuação">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                    <line x1="15" y1="9" x2="9" y2="15"></line>
                                                    <line x1="9" y1="9" x2="15" y2="15"></line>
                                                </svg>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" action="<?php echo url('divulgacao/excluirEmLote/' . (int) $evento['id']); ?>" onsubmit="return confirm('Excluir esta comprovação? Ela sai da lista e das somas, continua na trilha de auditoria, e a mesma imagem continua barrada.');"><?= campoCsrf() ?>
                                        <input type="hidden" name="comprovacao_ids[]" value="<?php echo (int) $comprovacao['id']; ?>">
                                        <button type="submit" class="btn-icone" title="Excluir">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                                                <path d="M10 11v6M14 11v6"></path>
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
        <p>Com as selecionadas:</p>
        <p>
            <?php if ($vendoExcluidas): ?>
                <button type="submit" form="form-acoes-em-massa" formaction="<?php echo url('divulgacao/restaurarEmLote/' . (int) $evento['id']); ?>"
                        onclick="return confirm('Restaurar as comprovações selecionadas?');">Restaurar</button>
                <?php /* A imagem da comprovação excluída continua guardada, então o
                caminho de apagá-la precisa existir também aqui. */ ?>
                <button type="submit" form="form-acoes-em-massa" formaction="<?php echo url('divulgacao/apagarImagensEmLote/' . (int) $evento['id']); ?>"
                        onclick="return confirm('Apagar definitivamente a imagem das comprovações selecionadas?');">Apagar as imagens</button>
            <?php else: ?>
                <label>Motivo da anulação
                    <input type="text" name="motivo" maxlength="500" size="40" form="form-acoes-em-massa" placeholder="O participante lê este motivo">
                </label>
                <button type="submit" form="form-acoes-em-massa" formaction="<?php echo url('divulgacao/anularEmLote/' . (int) $evento['id']); ?>"
                        onclick="return confirm('Anular a pontuação das comprovações selecionadas?');">Anular</button>
                <button type="submit" form="form-acoes-em-massa" formaction="<?php echo url('divulgacao/reverterAnulacaoEmLote/' . (int) $evento['id']); ?>"
                        onclick="return confirm('Desfazer a anulação das comprovações selecionadas?');">Desfazer anulação</button>
                <button type="submit" form="form-acoes-em-massa" formaction="<?php echo url('divulgacao/apagarImagensEmLote/' . (int) $evento['id']); ?>"
                        onclick="return confirm('Apagar definitivamente a imagem das comprovações selecionadas? As comprovações e os pontos continuam.');">Apagar as imagens</button>
                <button type="submit" form="form-acoes-em-massa" formaction="<?php echo url('divulgacao/excluirEmLote/' . (int) $evento['id']); ?>"
                        onclick="return confirm('Excluir as comprovações selecionadas? Elas saem da lista e das somas, continuam na trilha de auditoria, e as mesmas imagens continuam barradas.');">Excluir</button>
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <?php if ((int) $totalPaginas > 1): ?>
        <p class="auditoria-paginacao">
            <span>Página <?php echo (int) $pagina; ?> de <?php echo (int) $totalPaginas; ?></span>
            <?php if ((int) $pagina > 1): ?>
                <a href="<?php echo htmlspecialchars(url('divulgacao/comprovacoes/' . (int) $evento['id']) . '&' . http_build_query($parametrosFiltro + ['pagina' => $pagina - 1]), ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Página anterior">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </a>
            <?php endif; ?>
            <?php if ((int) $pagina < (int) $totalPaginas): ?>
                <a href="<?php echo htmlspecialchars(url('divulgacao/comprovacoes/' . (int) $evento['id']) . '&' . http_build_query($parametrosFiltro + ['pagina' => $pagina + 1]), ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Próxima página">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </a>
            <?php endif; ?>
        </p>
    <?php endif; ?>
<?php endif; ?>
