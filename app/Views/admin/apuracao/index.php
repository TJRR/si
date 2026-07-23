<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Apuração — <?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<h2>Fórmula da nota final</h2>
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

    <?php if (\App\Core\Auth::possuiPerfil('administrador')): ?>
    <form method="post" action="<?php echo url('formulas/trilha/' . (int) $trilha['id']); ?>">
        <label>Expressão (ex.: NE2*0.4 + NE3*0.6):<br>
            <textarea name="expressao" rows="3" cols="70" required><?php echo htmlspecialchars((string) $expressaoAtual, ENT_QUOTES, 'UTF-8'); ?></textarea>
        </label><br>
        <button type="submit" name="acao" value="salvar">Salvar fórmula</button>
    </form>
    <?php else: ?>
        <p><strong>Expressão atual:</strong> <?php echo htmlspecialchars((string) $expressaoAtual, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
<?php endif; ?>

<h2>Desempate</h2>
<?php if (\App\Core\Auth::possuiPerfil('administrador')): ?>
<p><a href="<?php echo url('desempate/index/' . (int) $trilha['id']); ?>">Gerenciar regras de desempate (por etapa)</a></p>
<?php endif; ?>
<p>Ordem de aplicação em caso de empate na Nota Final (1ª linha tem prioridade). Esta lista junta as regras de todas as etapas da trilha, na ordem em que se aplicam à Nota Final:</p>

<?php if (empty($regras)): ?>
    <p>Nenhuma regra de desempate cadastrada.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Ordem</th><th>Etapa</th><th>Critério</th><th>Direção</th><th>Ações</th></tr>
        <?php foreach ($regras as $regra): ?>
        <tr>
            <td><?php echo (int) $regra['ordem']; ?></td>
            <td><?php echo htmlspecialchars($regra['etapa_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo $regra['tipo'] === 'data_submissao' ? 'Data de inscrição (quem enviou primeiro)' : htmlspecialchars($regra['criterio_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo $regra['direcao'] === 'asc' ? 'Crescente (menor valor vence)' : 'Decrescente (maior valor vence)'; ?></td>
            <td>
                <?php if (\App\Core\Auth::possuiPerfil('administrador')): ?>
                <div class="acoes-icones">
                    <form method="post" action="<?php echo url('desempate/mover'); ?>">
                        <input type="hidden" name="id" value="<?php echo (int) $regra['id']; ?>">
                        <input type="hidden" name="trilha_id" value="<?php echo (int) $trilha['id']; ?>">
                        <input type="hidden" name="direcao" value="cima">
                        <button type="submit" class="btn-icone" title="Mover para cima">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <line x1="12" y1="19" x2="12" y2="5"></line>
                                <polyline points="5 12 12 5 19 12"></polyline>
                            </svg>
                        </button>
                    </form>
                    <form method="post" action="<?php echo url('desempate/mover'); ?>">
                        <input type="hidden" name="id" value="<?php echo (int) $regra['id']; ?>">
                        <input type="hidden" name="trilha_id" value="<?php echo (int) $trilha['id']; ?>">
                        <input type="hidden" name="direcao" value="baixo">
                        <button type="submit" class="btn-icone" title="Mover para baixo">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <polyline points="19 12 12 19 5 12"></polyline>
                            </svg>
                        </button>
                    </form>
                    <form method="post" action="<?php echo url('desempate/remover'); ?>">
                        <input type="hidden" name="id" value="<?php echo (int) $regra['id']; ?>">
                        <input type="hidden" name="trilha_id" value="<?php echo (int) $trilha['id']; ?>">
                        <button type="submit" class="btn-icone" title="Remover">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                                <path d="M10 11v6"></path>
                                <path d="M14 11v6"></path>
                                <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                            </svg>
                        </button>
                    </form>
                </div>
                <?php else: ?>
                    —
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<h2>Resultado final</h2>

<?php if ($erroResultado !== null): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erroResultado, ENT_QUOTES, 'UTF-8'); ?></p>
<?php elseif (empty($ranking)): ?>
    <p>Nenhuma equipe com todas as etapas publicadas ainda — publique o resultado de cada etapa da trilha antes de calcular a nota final.</p>
<?php else: ?>
    <p>
        <?php if ($publicado): ?>
            <strong>Resultado final publicado.</strong>
            <?php if (\App\Core\Auth::possuiPerfil('administrador')): ?>
            <form method="post" action="<?php echo url('resultados/reabrirTrilha'); ?>" style="display:inline;">
                <input type="hidden" name="trilha_id" value="<?php echo (int) $trilha['id']; ?>">
                <button type="submit" class="btn-icone" title="Reabrir" onclick="return confirm('Reabrir apaga o resultado final publicado. Confirmar?');">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="1 4 1 10 7 10"></polyline>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                    </svg>
                </button>
            </form>
            <?php endif; ?>
        <?php else: ?>
            <strong>Prévia (ainda não publicada)</strong> — recalculada a cada acesso.
            <?php if (\App\Core\Auth::possuiPerfil('administrador')): ?>
            <form method="post" action="<?php echo url('resultados/publicarTrilha'); ?>" style="display:inline;">
                <input type="hidden" name="trilha_id" value="<?php echo (int) $trilha['id']; ?>">
                <button type="submit" class="btn-icone" title="Confirmar e publicar" onclick="return confirm('Publicar congela a colocação final desta trilha. Confirmar?');">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </button>
            </form>
            <?php endif; ?>
        <?php endif; ?>
    </p>

    <table border="1" cellpadding="6">
        <tr><th>Colocação</th><th>Equipe</th><th>NF</th></tr>
        <?php foreach ($ranking as $linha): ?>
        <tr>
            <td><?php echo (int) $linha['colocacao']; ?></td>
            <td><?php echo htmlspecialchars($linha['nome_equipe'] !== null ? $linha['nome_equipe'] : '—', ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo number_format((float) $linha['nf'], 2, ',', '.'); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
