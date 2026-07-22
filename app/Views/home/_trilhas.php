<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<section class="site-section" id="trilhas">
    <h2 class="section-title">Trilhas</h2>
    <?php if (empty($trilhasAtivas)): ?>
        <p class="section-text">Trilhas em definição.</p>
    <?php else: ?>
        <div class="site-trilhas-grid">
            <?php foreach ($trilhasAtivas as $trilha): ?>
            <?php
            $documentosDaTrilha = array_values(array_filter($documentos, function ($documento) use ($trilha) {
                return (int) $documento['trilha_id'] === (int) $trilha['id'];
            }));
            $inscricaoDaTrilha = null;
            foreach ($trilhasComInscricaoAberta as $item) {
                if ($item['trilha_nome'] === $trilha['nome']) {
                    $inscricaoDaTrilha = $item;
                    break;
                }
            }
            $homologacaoDaTrilha = null;
            foreach ($trilhasComHomologacaoPublicada as $item) {
                if ((int) $item['trilha_id'] === (int) $trilha['id']) {
                    $homologacaoDaTrilha = $item;
                    break;
                }
            }
            $resultadosDaTrilha = array_values(array_filter($etapasComResultadoPublicado, function ($item) use ($trilha) {
                return $item['trilha_nome'] === $trilha['nome'];
            }));
            $etapasDaTrilha = array_values(array_filter($cronograma, function ($item) use ($trilha) {
                return $item['tipo'] === 'etapa' && $item['trilha_nome'] === $trilha['nome'];
            }));
            ?>
            <div class="admin-card site-trilha-card">
                <h3><?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <?php if (!empty($trilha['descricao'])): ?>
                    <p><?php echo nl2br(htmlspecialchars($trilha['descricao'], ENT_QUOTES, 'UTF-8')); ?></p>
                <?php endif; ?>
                <?php if (!empty($etapasDaTrilha) || $homologacaoDaTrilha !== null || !empty($resultadosDaTrilha)): ?>
                    <ul class="site-trilha-links">
                        <?php foreach ($etapasDaTrilha as $etapaItem): ?>
                            <li class="site-trilha-link-item">
                                <span class="site-trilha-link-texto">
                                    <strong><?php echo htmlspecialchars($etapaItem['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span class="site-trilha-link-meta">
                                        <?php echo htmlspecialchars(formatarData($etapaItem['data_inicio']), ENT_QUOTES, 'UTF-8'); ?>
                                        <?php if ($etapaItem['data_fim']): ?>
                                            a <?php echo htmlspecialchars(formatarData($etapaItem['data_fim']), ENT_QUOTES, 'UTF-8'); ?>
                                        <?php endif; ?>
                                    </span>
                                </span>
                            </li>
                        <?php endforeach; ?>
                        <?php if ($homologacaoDaTrilha !== null): ?>
                            <li class="site-trilha-link-item">
                                <svg class="site-trilha-link-icone" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                                <a href="<?php echo url('homologacaoPublica/trilha/' . (int) $homologacaoDaTrilha['trilha_id']); ?>">Ver equipes homologadas</a>
                            </li>
                        <?php endif; ?>
                        <?php foreach ($resultadosDaTrilha as $item): ?>
                            <li class="site-trilha-link-item">
                                <svg class="site-trilha-link-icone" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="12" cy="8" r="7"></circle>
                                    <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>
                                </svg>
                                <a href="<?php echo url('resultadosPublicos/etapa/' . (int) $item['etapa_id']); ?>"><?php echo htmlspecialchars('Ver resultado — ' . $item['etapa_nome'], ENT_QUOTES, 'UTF-8'); ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if (!empty($documentosDaTrilha)): ?>
                    <ul class="site-trilha-documentos">
                        <?php foreach ($documentosDaTrilha as $documento): ?>
                            <li class="site-trilha-documento">
                                <svg class="site-trilha-documento-icone" width="28" height="28" viewBox="0 0 24 24" aria-hidden="true">
                                    <path fill="#E5252A" d="M6 2a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6H6z"/>
                                    <path fill="#ffffff" fill-opacity="0.85" d="M14 2v6h6z"/>
                                    <text x="12" y="17" text-anchor="middle" font-size="7" font-family="Arial, sans-serif" font-weight="bold" fill="#ffffff">PDF</text>
                                </svg>
                                <span class="site-trilha-documento-texto">
                                    <a href="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $documento['arquivo_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($documento['titulo'], ENT_QUOTES, 'UTF-8'); ?></a>
                                    <span class="site-trilha-documento-meta">
                                        <?php echo htmlspecialchars(\App\Core\Texto::dataAbreviada($documento['criado_em']), ENT_QUOTES, 'UTF-8'); ?>
                                        · <?php echo htmlspecialchars($documento['criado_por_nome'] !== null ? $documento['criado_por_nome'] : 'Sistema', ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if ($inscricaoDaTrilha !== null): ?>
                    <a href="<?php echo url('inscricao/formulario/' . (int) $inscricaoDaTrilha['etapa_id']); ?>" class="btn btn-cta">Inscreva-se — <?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
