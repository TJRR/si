<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>

<div class="site-page">
    <header class="site-header">
        <div class="site-header-inner">
            <img src="<?php echo htmlspecialchars($logoAdminSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Prêmio de Inovação TJRR" class="site-logo">
            <nav class="site-nav">
                <?php if (isset($ajudaHtml) && $ajudaHtml !== null): ?>
                <button type="button" class="site-header-icone" title="Ajuda desta página" aria-label="Ajuda desta página" data-ajuda-titulo="<?php echo htmlspecialchars('Ajuda — ' . (string) $ajudaTitulo, ENT_QUOTES, 'UTF-8'); ?>" onclick="abrirModal(this.dataset.ajudaTitulo, document.getElementById('ajuda-painel-fonte').innerHTML)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </button>
                <?php endif; ?>
                <a href="<?php echo url('home/index'); ?>" class="btn">Voltar ao início</a>
            </nav>
        </div>
    </header>

    <div class="site-form-page">
        <h1>Resultado — <?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

        <?php if (empty($equipes)): ?>
            <p><em>Nenhuma equipe classificada nesta etapa.</em></p>
        <?php elseif ($modo === 'apenas_classificados'): ?>
            <?php foreach ($equipes as $equipe): ?>
                <div style="padding:1em 0; border-bottom:1px solid var(--cor-borda);">
                    <h3><?php echo htmlspecialchars($equipe['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?></h3>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <table border="1" cellpadding="8" style="width:100%; border-collapse:collapse;">
                <tr>
                    <th style="text-align:left;">Ordem</th>
                    <th style="text-align:left;">Nome da Equipe</th>
                    <th style="text-align:left;">Nota Geral</th>
                </tr>
                <?php $ultimoEraClassificado = null; ?>
                <?php foreach ($equipes as $equipe): ?>
                    <?php if ($ultimoEraClassificado === true && $equipe['classificado'] === false): ?>
                        <tr><td colspan="3" style="text-align:center; font-style:italic; border-top:2px dashed var(--cor-borda);">Linha de corte — abaixo, equipes não classificadas</td></tr>
                    <?php endif; ?>
                    <?php $ultimoEraClassificado = $equipe['classificado']; ?>
                    <tr>
                        <td><?php echo (int) $equipe['posicao']; ?>º</td>
                        <td>
                            <?php echo htmlspecialchars($equipe['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?>
                            <?php if (!$equipe['classificado']): ?>
                                <span class="status-pill laranja">Não classificada</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $equipe['ne'] !== null ? number_format((float) $equipe['ne'], 2, ',', '.') : '—'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <?php if ($modo === 'ranking_e_material'): ?>
                <?php foreach ($equipes as $equipe): ?>
                    <?php if ($equipe['material'] === null): ?>
                        <?php continue; ?>
                    <?php endif; ?>
                    <div style="padding:1em 0; border-bottom:1px solid var(--cor-borda);">
                        <h3><?php echo htmlspecialchars($equipe['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?></h3>

                        <?php if (empty($equipe['material'])): ?>
                            <p><em>Nenhum material disponível.</em></p>
                        <?php else: ?>
                            <?php foreach ($equipe['material'] as $item): ?>
                                <?php $campo = $item['campo']; $valor = $item['valor']; ?>
                                <?php if ($valor === null || $valor === ''): ?>
                                    <?php continue; ?>
                                <?php endif; ?>
                                <div class="ficha-item">
                                    <div class="ficha-item-label"><?php echo htmlspecialchars($campo['rotulo'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="ficha-item-valor">
                                        <?php if ($campo['tipo'] === 'link_youtube'): ?>
                                            <?php $videoId = \App\Validation\YoutubeValidador::extrairId($valor); ?>
                                            <?php if ($videoId !== null): ?>
                                                <div style="position:relative; width:100%; max-width:480px; padding-top:270px;">
                                                    <iframe src="https://www.youtube.com/embed/<?php echo htmlspecialchars($videoId, ENT_QUOTES, 'UTF-8'); ?>"
                                                            style="position:absolute; top:0; left:0; width:100%; height:100%; border:0;"
                                                            allowfullscreen></iframe>
                                                </div>
                                            <?php elseif (\App\Validation\YoutubeValidador::valido($valor)): ?>
                                                <a href="<?php echo htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); ?></a>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); ?>
                                            <?php endif; ?>
                                        <?php elseif ($campo['tipo'] === 'link_externo'): ?>
                                            <?php if (linkHttpValido($valor)): ?>
                                                <a href="<?php echo htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); ?></a>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php echo nl2br(htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8')); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
