<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Estandes: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <?php if ($podeEditar): ?>
        <a href="<?php echo url('estandes/novo/' . (int) $evento['id']); ?>" class="btn-acao">+ Novo estande</a>
        <?php endif; ?>
        <a href="<?php echo url('eventos/editar/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<p style="color:#555;font-size:0.9em;">Cada estande tem um código de visita fixo, impresso no cartaz. O participante lê esse código no aplicativo do evento e ganha os pontos da visita, uma vez por estande. A lista pública aparece na página do evento quando o componente "Estandes" é acrescentado em Seções da página.</p>

<?php
$parametrosFiltro = array_filter($filtros, function ($valor) {
    return $valor !== '' && $valor !== null;
});
?>
<div class="filtros-barra-wrapper">
    <form method="get" action="<?php echo config('base_path'); ?>/index.php" class="filtros-barra">
        <input type="hidden" name="r" value="estandes/index/<?php echo (int) $evento['id']; ?>">
        <label class="filtro-busca">Busca:
            <input type="text" name="busca" placeholder="Nome do estande ou representante" value="<?php echo htmlspecialchars($filtros['busca'], ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <label>Categoria:
            <select name="categoria">
                <option value="">Todas</option>
                <?php foreach (\App\Repositories\EstandeRepository::CATEGORIAS as $chave => $rotulo): ?>
                    <option value="<?php echo htmlspecialchars($chave, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $filtros['categoria'] === $chave ? ' selected' : ''; ?>><?php echo htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Situação:
            <select name="situacao">
                <option value="">Todas</option>
                <option value="ativos"<?php echo $filtros['situacao'] === 'ativos' ? ' selected' : ''; ?>>Ativos</option>
                <option value="inativos"<?php echo $filtros['situacao'] === 'inativos' ? ' selected' : ''; ?>>Inativos</option>
            </select>
        </label>
        <div class="filtros-barra-acoes">
            <button type="submit" class="btn-icone" title="Filtrar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                </svg>
            </button>
            <a href="<?php echo url('estandes/index/' . (int) $evento['id']); ?>" class="btn-icone" title="Limpar filtros">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="1 4 1 10 7 10"></polyline>
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                </svg>
            </a>
        </div>
    </form>
</div>

<?php if (empty($estandes)): ?>
    <p>Nenhum estande encontrado<?php echo $parametrosFiltro !== [] ? ' com estes filtros' : ' neste evento ainda'; ?>.</p>
<?php else: ?>
    <?php /* O arraste é o padrão de ordenação do projeto e vale sobre a lista
    inteira: com filtro aplicado, ele fica desligado, para não gravar uma ordem
    tirada de uma lista parcial. */ ?>
    <?php $podeArrastar = $podeEditar && $parametrosFiltro === []; ?>
    <?php if ($podeEditar && $parametrosFiltro !== []): ?>
        <p class="status-pill laranja">Com filtro aplicado, a ordenação por arraste fica desligada. Limpe os filtros para reordenar.</p>
    <?php endif; ?>
    <ul class="reordenar-lista"<?php echo $podeArrastar ? ' data-reordenar-rota="estandes/reordenar/' . (int) $evento['id'] . '"' : ''; ?>>
        <?php foreach ($estandes as $indice => $estande): ?>
        <li class="reordenar-item"<?php echo $podeArrastar ? ' draggable="true"' : ''; ?> data-id="<?php echo (int) $estande['id']; ?>">
            <?php if ($podeArrastar): ?>
                <span class="reordenar-alca" aria-hidden="true" title="Arraste para reordenar">⠿</span>
            <?php endif; ?>
            <?php if (!empty($estande['logotipo_path'])): ?>
                <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $estande['logotipo_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="" style="width:72px;height:40px;object-fit:contain;border-radius:4px;background:#fff;">
            <?php else: ?>
                <span style="width:72px;height:40px;border-radius:4px;display:inline-block;background:#eee;"></span>
            <?php endif; ?>
            <div class="reordenar-conteudo">
                <strong><?php echo htmlspecialchars($estande['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <br>
                <span class="status-pill"><?php echo htmlspecialchars(\App\Repositories\EstandeRepository::CATEGORIAS[$estande['categoria']], ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="status-pill <?php echo $estande['ativo'] ? 'verde' : 'vermelho'; ?>"><?php echo $estande['ativo'] ? 'Ativo' : 'Inativo'; ?></span>
                <br>
                <small>
                    <?php echo (int) $estande['pontos_visita']; ?> <?php echo (int) $estande['pontos_visita'] === 1 ? 'ponto' : 'pontos'; ?> por visita
                    · <?php echo (int) $estande['total_visitas']; ?> <?php echo (int) $estande['total_visitas'] === 1 ? 'visita registrada' : 'visitas registradas'; ?>
                    · Representante: <?php echo !empty($estande['representante_nome']) ? htmlspecialchars($estande['representante_nome'], ENT_QUOTES, 'UTF-8') : 'nenhum'; ?>
                </small>
            </div>
            <div class="acoes-icones">
                <a href="<?php echo url('estandes/editar/' . (int) $estande['id']); ?>" class="btn-icone" title="<?php echo $podeEditar ? 'Editar' : 'Ver'; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </a>
                <a href="<?php echo url('estandes/codigo/' . (int) $estande['id']); ?>" class="btn-icone" title="Imprimir cartaz" target="_blank" rel="noopener">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                </a>
                <?php if ($podeEditar): ?>
                <form method="post" action="<?php echo url('estandes/remover'); ?>" onsubmit="return confirm('Remover este estande? Só funciona se ele ainda não tiver visitas. Se houver representante, ele perde o acesso ao estande.');"><?= campoCsrf() ?>
                    <input type="hidden" name="id" value="<?php echo (int) $estande['id']; ?>">
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
                <?php endif; ?>
            </div>
            <?php if ($podeArrastar): ?>
            <div class="reordenar-botoes">
                <button type="button" class="btn-icone" data-mover="cima" aria-label="Mover para cima" <?php echo $indice === 0 ? 'disabled' : ''; ?>>▲</button>
                <button type="button" class="btn-icone" data-mover="baixo" aria-label="Mover para baixo" <?php echo $indice === count($estandes) - 1 ? 'disabled' : ''; ?>>▼</button>
            </div>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
