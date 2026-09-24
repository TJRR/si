<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
// Fase 50: pagina publica de entrada propria do Evento (ver
// EventoPublicoController::index()). Reaproveita o cabecalho, o carrossel e
// o rodape da home do Concurso; o resto (faixas, blocos e componentes) tem
// partials proprios em publico/evento/, para a pagina do Evento seguir a
// identidade visual aprovada sem mexer em nada da home do Concurso.
$estiloCores = '--cor-primaria-inicio:' . htmlspecialchars($temaAtivo['cor_primaria_inicio'], ENT_QUOTES, 'UTF-8') . ';'
    . '--cor-primaria-fim:' . htmlspecialchars($temaAtivo['cor_primaria_fim'], ENT_QUOTES, 'UTF-8') . ';'
    . '--cor-secundaria:' . htmlspecialchars($temaAtivo['cor_secundaria'], ENT_QUOTES, 'UTF-8') . ';';

// Reabertura da Fase 51: fontes escolhidas na aba Cabecalho do evento (a
// folha de estilo delas e' pedida no cabecalho de layout.php). Os nomes
// saem de uma lista fechada, EventoConfiguracaoVisualRepository::FONTES.
if ($fonteTituloEvento !== null) {
    $estiloCores .= "--fonte-titulo:'" . htmlspecialchars($fonteTituloEvento, ENT_QUOTES, 'UTF-8') . "', -apple-system, 'Segoe UI', sans-serif;";
}

if ($fonteTextoEvento !== null) {
    $estiloCores .= "--fonte-corpo:'" . htmlspecialchars($fonteTextoEvento, ENT_QUOTES, 'UTF-8') . "', -apple-system, 'Segoe UI', sans-serif;";
}

// Correcao (pos-teste de fumaca da Fase 50): estas 3 variaveis nao sao
// calculadas dentro de home/_cabecalho.php, e sim por quem inclui esse
// arquivo.
$temImagemCabecalho = !empty($configVisual['cabecalho_imagem_path']);
$urlImagemCabecalho = $temImagemCabecalho ? config('base_path') . '/assets/' . $configVisual['cabecalho_imagem_path'] : null;
$logoClaroSrc = !empty($configVisual['cabecalho_logo_claro_path']) ? config('base_path') . '/assets/' . $configVisual['cabecalho_logo_claro_path'] : null;
?>
<div class="site-page evento-pagina<?php echo $temLogoEvento ? ' evento-com-logo' : ''; ?>" id="topo" style="<?php echo $estiloCores; ?>">
    <a href="#conteudo-principal" class="skip-link">Pular para o conteúdo principal</a>

    <?php include __DIR__ . '/../home/_cabecalho.php'; ?>

    <main id="conteudo-principal">
        <?php if (!empty($_SESSION['flash'])): ?>
            <p class="site-flash <?php echo classeFlash(); ?>"><?php echo htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['flash']); ?></p>
        <?php endif; ?>

        <?php foreach ($secoesOrdenadas as $secao): ?>
            <?php $ancoraSecao = \App\Repositories\EventoSecaoOrdemRepository::ancoraDaSecao($secao); ?>
            <?php if ($secao['tipo'] === 'quadros'): ?>
                <?php if (!empty($slides)): ?>
                    <div class="evento-carrossel">
                        <?php include __DIR__ . '/../home/_slideshow.php'; ?>
                    </div>
                <?php endif; ?>
            <?php elseif ($secao['tipo'] === 'faixas'): ?>
                <?php include __DIR__ . '/evento/_faixas.php'; ?>
            <?php elseif ($secao['tipo'] === 'bloco'): ?>
                <?php if (isset($blocosPorId[$secao['referencia_id']])): ?>
                    <?php $blocoEvento = $blocosPorId[$secao['referencia_id']]; ?>
                    <?php include __DIR__ . '/evento/_bloco.php'; ?>
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
