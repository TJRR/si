<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
// Fase 51, refeita na reabertura: local em duas colunas, como na identidade
// visual aprovada. A esquerda, etiqueta, titulo, endereco e texto de apoio;
// a direita, quadro branco arredondado com o mapa incorporado (ou a imagem
// do local). Sem mapa nem imagem, o texto ocupa a largura toda.
$cabecalhoProprio = true;
$temMapa = !empty($dadosSecao['mapa_embed_url']);
$temImagem = !$temMapa && !empty($dadosSecao['imagem_path']);

// Reabertura da Fase 51 (teste de fumaca, item 9): sem endereco proprio do
// botao "Abrir no mapa", vale o endereco do mapa de Configuracoes, Contato
// (o mesmo do rodape), conferido de novo aqui como no rodape.
$linkAbrirMapa = !empty($dadosSecao['mapa_link'])
    ? $dadosSecao['mapa_link']
    : ($contato !== null && isset($contato['mapa_url']) ? $contato['mapa_url'] : null);
$linkAbrirMapa = linkHttpValido($linkAbrirMapa) ? $linkAbrirMapa : null;
?>
<?php include __DIR__ . '/_secao_abre.php'; ?>
        <div class="evento-duas-colunas evento-local<?php echo ($temMapa || $temImagem) ? '' : ' evento-local-sem-mapa'; ?>">
            <div class="evento-local-texto">
                <?php if (!empty($dadosSecao['etiqueta'])): ?>
                    <p class="evento-secao-etiqueta"><?php echo htmlspecialchars($dadosSecao['etiqueta'], ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
                <?php if (!empty($dadosSecao['titulo'])): ?>
                    <h2 class="evento-secao-titulo"><?php echo htmlspecialchars($dadosSecao['titulo'], ENT_QUOTES, 'UTF-8'); ?></h2>
                <?php endif; ?>
                <?php if (!empty($dadosSecao['endereco'])): ?>
                    <p class="evento-local-endereco"><?php echo htmlspecialchars($dadosSecao['endereco'], ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
                <?php if (!empty($dadosSecao['descricao_html'])): ?>
                    <div class="evento-local-apoio"><?php echo $dadosSecao['descricao_html']; ?></div>
                <?php endif; ?>
                <?php if ($linkAbrirMapa !== null): ?>
                    <div class="evento-botoes">
                        <a href="<?php echo htmlspecialchars($linkAbrirMapa, ENT_QUOTES, 'UTF-8'); ?>" class="evento-botao evento-botao-contorno" target="_blank" rel="noopener noreferrer">Abrir no mapa</a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($temMapa): ?>
                <div class="evento-local-mapa">
                    <iframe src="<?php echo htmlspecialchars($dadosSecao['mapa_embed_url'], ENT_QUOTES, 'UTF-8'); ?>" title="Mapa do local do evento" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                </div>
            <?php elseif ($temImagem): ?>
                <div class="evento-local-mapa">
                    <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $dadosSecao['imagem_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $dadosSecao['imagem_alt'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            <?php endif; ?>
        </div>
<?php include __DIR__ . '/_secao_fecha.php'; ?>
