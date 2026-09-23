<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
// Fase 51: destaques da programacao. No modo vinculado os itens sao
// Atividades do proprio evento (nome, data e local vem de la); no modo
// digitado, sao os itens cadastrados na secao. O que foi digitado no item
// sempre vence o dado da atividade, para a chamada da pagina poder ser
// diferente do nome oficial.
$vinculado = $dadosSecao['fonte'] === 'atividades';
?>
<?php include __DIR__ . '/_secao_abre.php'; ?>
        <?php if (!empty($itensSecao)): ?>
            <div class="evento-destaques evento-cartoes-colunas-<?php echo (int) $dadosSecao['colunas']; ?>">
                <?php foreach ($itensSecao as $item): ?>
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
                    $tipoNome = !empty($item['tipo_nome']) ? $item['tipo_nome'] : (!$vinculado && !empty($item['tipo_texto']) ? $item['tipo_texto'] : '');
                    $corTipo = !empty($item['tipo_cor']) ? $item['tipo_cor'] : null;
                    ?>
                    <article class="evento-destaque">
                        <?php if ($tipoNome !== ''): ?>
                            <span class="evento-etiqueta-tipo" style="<?php echo $corTipo !== null ? 'background:' . htmlspecialchars($corTipo, ENT_QUOTES, 'UTF-8') . ';' : ''; ?>"><?php echo htmlspecialchars($tipoNome, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars((string) $titulo, ENT_QUOTES, 'UTF-8'); ?></h3>
                        <?php if ($quando !== ''): ?>
                            <p class="evento-destaque-quando"><?php echo htmlspecialchars($quando, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                        <?php if ($local !== ''): ?>
                            <p class="evento-destaque-local"><?php echo htmlspecialchars($local, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                        <?php if (!$vinculado && !empty($item['descricao'])): ?>
                            <p><?php echo htmlspecialchars($item['descricao'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
<?php include __DIR__ . '/_secao_fecha.php'; ?>
