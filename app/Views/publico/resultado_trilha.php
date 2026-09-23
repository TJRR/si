<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
// Fase 51: resultado final da trilha, publico. Mesma casca da tela de
// resultado por etapa (publico/resultado_etapa.php), com a diferenca de
// mostrar a colocacao final e o destaque de cada case, quando cadastrado.
?>
<div class="site-page">
    <header class="site-header">
        <div class="site-header-inner">
            <img src="<?php echo htmlspecialchars($logoAdminSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars('Prêmio de Inovação ' . nomeInstituicao(), ENT_QUOTES, 'UTF-8'); ?>" class="site-logo">
            <nav class="site-nav">
                <a href="<?php echo url('home/index'); ?>" class="btn">Voltar ao início</a>
            </nav>
        </div>
    </header>

    <div class="site-form-page">
        <h1>Resultado final: <?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

        <?php if (empty($equipes)): ?>
            <p><em>Nenhum resultado a exibir nesta trilha.</em></p>
        <?php else: ?>
            <?php if ($modo === 'ranking_completo'): ?>
                <table border="1" cellpadding="8" style="width:100%; border-collapse:collapse;">
                    <tr>
                        <th style="text-align:left;">Colocação</th>
                        <th style="text-align:left;">Nome da Equipe</th>
                        <th style="text-align:left;">Nota Final</th>
                    </tr>
                    <?php foreach ($equipes as $equipe): ?>
                        <tr>
                            <td><?php echo (int) $equipe['colocacao']; ?>º</td>
                            <td><?php echo htmlspecialchars($equipe['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo $equipe['nf'] !== null ? number_format((float) $equipe['nf'], $casasDecimais, ',', '.') : 'Sem nota'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>

            <?php foreach ($equipes as $equipe): ?>
                <?php
                $temDestaque = trim((string) $equipe['resumo_destaque']) !== '' || !empty($equipe['imagem_destaque_path']);
                ?>
                <?php if (!$temDestaque): ?>
                    <?php continue; ?>
                <?php endif; ?>
                <div style="padding:1em 0; border-bottom:1px solid var(--cor-borda);">
                    <h3><?php echo (int) $equipe['colocacao']; ?>º lugar: <?php echo htmlspecialchars($equipe['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?></h3>

                    <?php if (!empty($equipe['imagem_destaque_path'])): ?>
                        <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $equipe['imagem_destaque_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $equipe['imagem_destaque_alt'], ENT_QUOTES, 'UTF-8'); ?>" style="max-width:100%;border-radius:10px;margin:.5rem 0;">
                    <?php endif; ?>

                    <?php if (trim((string) $equipe['resumo_destaque']) !== ''): ?>
                        <div class="section-text"><?php echo nl2br(htmlspecialchars($equipe['resumo_destaque'], ENT_QUOTES, 'UTF-8')); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
