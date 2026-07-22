<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
$iconesVisual = \App\Repositories\TemaRepository::ICONES_VISUAL;
$gruposComTemas = array_values(array_filter($temasPorTrilha, function ($grupo) {
    return !empty($grupo['temas']);
}));
?>
<?php if (!empty($gruposComTemas)): ?>
<section class="site-section" id="temas">
    <h2 class="section-title">Desafios</h2>

    <?php if (count($gruposComTemas) > 1): ?>
        <div class="temas-abas" role="tablist">
            <?php foreach ($gruposComTemas as $indiceTrilha => $grupo): ?>
                <?php
                $totalDesafiosTrilha = 0;
                foreach ($grupo['temas'] as $temaContagem) {
                    $totalDesafiosTrilha += count($temaContagem['desafios']);
                }
                ?>
                <button type="button" class="temas-aba<?php echo $indiceTrilha === 0 ? ' ativo' : ''; ?>" data-temas-aba="<?php echo $indiceTrilha; ?>" role="tab" aria-selected="<?php echo $indiceTrilha === 0 ? 'true' : 'false'; ?>">
                    <?php echo htmlspecialchars($grupo['trilha']['nome'], ENT_QUOTES, 'UTF-8'); ?>
                    <span class="temas-aba-contador"><?php echo $totalDesafiosTrilha; ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <label class="temas-busca-rotulo">
        <input type="search" class="temas-busca" placeholder="Buscar por palavra-chave nos desafios" data-temas-busca aria-label="Buscar por palavra-chave nos desafios">
    </label>

    <?php foreach ($gruposComTemas as $indiceTrilha => $grupo): ?>
        <div class="temas-eixo-conteudo<?php echo $indiceTrilha === 0 ? ' ativo' : ''; ?>" data-temas-conteudo="<?php echo $indiceTrilha; ?>">
            <?php foreach ($grupo['temas'] as $indiceTema => $tema): ?>
                <?php $iconeTema = !empty($tema['icone']) && isset($iconesVisual[$tema['icone']]) ? $iconesVisual[$tema['icone']] : null; ?>
                <div class="tema-card" data-tema-card>
                    <button type="button" class="tema-card-cabecalho" data-tema-toggle aria-expanded="false">
                        <?php if ($iconeTema !== null): ?>
                            <span class="tema-icone-badge" style="background-color:<?php echo htmlspecialchars($iconeTema['cor'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"><?php echo $iconeTema['glifo']; ?></span>
                        <?php endif; ?>
                        <span class="tema-card-titulos">
                            <span class="tema-card-meta">
                                <span class="tema-numero-pill">Tema <?php echo $indiceTema + 1; ?></span>
                                <span class="tema-contador"><?php echo count($tema['desafios']); ?> desafios</span>
                            </span>
                            <span class="tema-nome"><?php echo htmlspecialchars($tema['nome'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php if (!empty($tema['descricao_longa'])): ?>
                                <span class="tema-descricao"><?php echo nl2br(htmlspecialchars($tema['descricao_longa'], ENT_QUOTES, 'UTF-8')); ?></span>
                            <?php endif; ?>
                        </span>
                        <svg class="tema-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <?php if (!empty($tema['desafios'])): ?>
                        <div class="tema-card-corpo">
                            <ol class="tema-desafios-lista">
                                <?php foreach ($tema['desafios'] as $desafio): ?>
                                    <?php $iconeDesafio = !empty($desafio['icone']) && isset($iconesVisual[$desafio['icone']]) ? $iconesVisual[$desafio['icone']] : null; ?>
                                    <li class="tema-desafio-item" data-tema-desafio-texto="<?php echo htmlspecialchars(mb_strtolower($desafio['pergunta'], 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php if ($iconeDesafio !== null): ?>
                                            <span class="tema-desafio-badge" style="background-color:<?php echo htmlspecialchars($iconeDesafio['cor'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"><?php echo $iconeDesafio['glifo']; ?></span>
                                        <?php endif; ?>
                                        <span class="tema-desafio-texto"><?php echo htmlspecialchars($desafio['pergunta'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</section>
<?php endif; ?>
