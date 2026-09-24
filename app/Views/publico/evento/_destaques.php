<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
// Fase 51, refeita na reabertura: destaques da programacao em cartoes
// brancos com icone num quadrado colorido, titulo, quando e onde, e a
// descricao. No modo vinculado os itens sao Atividades do proprio evento;
// no modo digitado, sao os itens cadastrados na secao (o que foi digitado
// no item sempre vence o dado da atividade). Os cartoes entram em
// sequencia quando aparecem na tela (evento-pagina.js).
$vinculado = $dadosSecao['fonte'] === 'atividades';
$icones = \App\Repositories\EventoSecaoDestaquesRepository::ICONES;
?>
<?php include __DIR__ . '/_secao_abre.php'; ?>
        <?php if (!empty($itensSecao)): ?>
            <div class="evento-destaques evento-colunas-<?php echo (int) $dadosSecao['colunas']; ?>">
                <?php foreach ($itensSecao as $indiceItem => $item): ?>
                    <?php
                    $titulo = $vinculado
                        ? $item['nome']
                        : (!empty($item['titulo']) ? $item['titulo'] : (string) $item['atividade_nome']);
                    $quando = $vinculado
                        ? formatarDataHora($item['data_inicio'])
                        : (!empty($item['quando_texto']) ? $item['quando_texto'] : (!empty($item['atividade_data_inicio']) ? formatarDataHora($item['atividade_data_inicio']) : ''));
                    $local = $vinculado
                        ? (string) $item['local']
                        : (!empty($item['local']) ? $item['local'] : (string) $item['atividade_local']);
                    $linhaQuando = implode(' · ', array_filter([$quando, $local], function ($parte) {
                        return trim((string) $parte) !== '';
                    }));
                    $chaveIcone = !$vinculado && !empty($item['icone']) && isset($icones[$item['icone']]) ? $item['icone'] : null;
                    ?>
                    <article class="evento-destaque" data-evento-entrada style="--ordem-entrada:<?php echo (int) $indiceItem; ?>;">
                        <?php if ($chaveIcone !== null): ?>
                            <span class="evento-destaque-icone" style="<?php echo estiloDeCores(!empty($item['icone_cor']) ? $item['icone_cor'] : null, corEhClara(!empty($item['icone_cor']) ? $item['icone_cor'] : null, 0.2) ? '#141413' : '#ffffff'); ?>" aria-hidden="true">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?php echo $icones[$chaveIcone]['svg']; ?></svg>
                            </span>
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars((string) $titulo, ENT_QUOTES, 'UTF-8'); ?></h3>
                        <?php if ($linhaQuando !== ''): ?>
                            <p class="evento-destaque-quando"><?php echo htmlspecialchars($linhaQuando, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                        <?php if (!$vinculado && !empty($item['descricao'])): ?>
                            <p class="evento-destaque-descricao"><?php echo htmlspecialchars($item['descricao'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
<?php include __DIR__ . '/_secao_fecha.php'; ?>
