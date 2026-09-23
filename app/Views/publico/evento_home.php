<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
// Fase 50: pagina publica de entrada propria do Evento (ver
// EventoPublicoController::index()). Mesma estrutura de
// app/Views/home/index.php (ramo com conteudo), mas reaproveitando os
// partials existentes que ja sao genericos o suficiente
// (_slideshow.php/_banners.php/_bloco_livre.php/_rodape.php nao
// referenciam nada exclusivo do Concurso) e _cabecalho.php generalizado
// nesta mesma fase para aceitar tanto $concursoAtivo quanto $evento.
$estiloCores = '--cor-primaria-inicio:' . htmlspecialchars($temaAtivo['cor_primaria_inicio'], ENT_QUOTES, 'UTF-8') . ';'
    . '--cor-primaria-fim:' . htmlspecialchars($temaAtivo['cor_primaria_fim'], ENT_QUOTES, 'UTF-8') . ';'
    . '--cor-secundaria:' . htmlspecialchars($temaAtivo['cor_secundaria'], ENT_QUOTES, 'UTF-8') . ';';

// Correcao (pos-teste de fumaca): estas 3 variaveis nao sao calculadas
// dentro de home/_cabecalho.php, sao calculadas por quem inclui esse
// arquivo (aqui, e tambem em home/index.php) - faltavam aqui, causando
// "Undefined variable" nas linhas que leem $temImagemCabecalho.
$temImagemCabecalho = !empty($configVisual['cabecalho_imagem_path']);
$urlImagemCabecalho = $temImagemCabecalho ? config('base_path') . '/assets/' . $configVisual['cabecalho_imagem_path'] : null;
$logoClaroSrc = !empty($configVisual['cabecalho_logo_claro_path']) ? config('base_path') . '/assets/' . $configVisual['cabecalho_logo_claro_path'] : null;
?>
<div class="site-page" id="topo" style="<?php echo $estiloCores; ?>">
    <a href="#conteudo-principal" class="skip-link">Pular para o conteúdo principal</a>

    <?php include __DIR__ . '/../home/_cabecalho.php'; ?>

    <main id="conteudo-principal">
        <?php if (!empty($_SESSION['flash'])): ?>
            <p class="site-flash <?php echo classeFlash(); ?>"><?php echo htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['flash']); ?></p>
        <?php endif; ?>

        <?php
        // Fase 51: a pagina segue a ordem cadastrada em "Seções da página".
        // Cada secao sabe se desenhar sozinha; os blocos continuam usando o
        // mesmo partial da home do Concurso, e os quadros e as faixas
        // tambem, so' que agora com a ancora da secao.
        $indiceAlternado = 0;
        ?>
        <?php foreach ($secoesOrdenadas as $secao): ?>
            <?php $ancoraSecao = \App\Repositories\EventoSecaoOrdemRepository::ancoraDaSecao($secao); ?>
            <?php if ($secao['tipo'] === 'quadros'): ?>
                <?php include __DIR__ . '/../home/_slideshow.php'; ?>
            <?php elseif ($secao['tipo'] === 'faixas'): ?>
                <?php include __DIR__ . '/../home/_banners.php'; ?>
            <?php elseif ($secao['tipo'] === 'bloco'): ?>
                <?php if (isset($blocosPorId[$secao['referencia_id']])): ?>
                    <?php $blocoLivre = $blocosPorId[$secao['referencia_id']]; ?>
                    <?php $alternado = $indiceAlternado % 2 === 1; $indiceAlternado++; ?>
                    <?php include __DIR__ . '/../home/_bloco_livre.php'; ?>
                <?php endif; ?>
            <?php elseif (isset($secao['dados'])): ?>
                <?php
                $dadosSecao = $secao['dados'];
                $itensSecao = $secao['itens'];
                $arquivoSecao = __DIR__ . '/evento/_' . $secao['tipo'] . '.php';
                ?>
                <?php if (is_file($arquivoSecao)): ?>
                    <?php include $arquivoSecao; ?>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>

    </main>

    <?php include __DIR__ . '/../home/_rodape.php'; ?>
</div>
