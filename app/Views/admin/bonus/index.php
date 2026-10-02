<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Bônus: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <?php if ($podeEditar): ?>
        <a href="<?php echo url('bonus/novo/' . (int) $evento['id']); ?>" class="btn-acao">+ Novo bônus</a>
        <?php endif; ?>
    </div>
</div>

<?php if ((int) $config['ativo'] === 1): ?>
    <p class="status-pill verde">Bônus ativados neste evento</p>
<?php else: ?>
    <p class="status-pill laranja">Bônus desativados neste evento: nada é apurado nem aparece no aplicativo</p>
<?php endif; ?>

<p style="color:#555;">
    Cada bônus é cadastrado aqui, com o nome que o participante vê, o tipo que o sistema sabe apurar,
    o que ele exige e quantos pontos vale. Dois bônus do mesmo tipo com exigências diferentes somam:
    quem cadastra "cinco atividades" e "dez atividades" premia as duas marcas.
</p>

<?php if (empty($bonus)): ?>
    <p>Nenhum bônus cadastrado ainda neste evento.</p>
<?php else: ?>
    <ul class="reordenar-lista" <?php echo $podeEditar ? 'data-reordenar-rota="' . 'bonus/reordenar/' . (int) $evento['id'] . '"' : ''; ?>>
        <?php foreach ($bonus as $indice => $item): ?>
        <?php
        $tipo = $item['tipo'];
        $rotuloTipo = isset($tipos[$tipo]) ? $tipos[$tipo]['rotulo'] : $tipo;
        $unidade = isset($tipos[$tipo]) ? $tipos[$tipo]['unidade'] : '';
        ?>
        <li class="reordenar-item" <?php echo $podeEditar ? 'draggable="true" data-id="' . (int) $item['id'] . '"' : ''; ?>>
            <?php if ($podeEditar): ?>
            <span class="reordenar-alca" aria-hidden="true" title="Arraste para reordenar">⠿</span>
            <?php endif; ?>
            <div class="reordenar-conteudo">
                <strong><?php echo htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <?php if ((int) $item['ativo'] !== 1): ?>
                    <span class="status-pill laranja">Desativado</span>
                <?php endif; ?>
                <br>
                <span>
                    <?php echo htmlspecialchars($rotuloTipo, ENT_QUOTES, 'UTF-8'); ?>
                    <?php if ((int) $item['exigencia'] > 0): ?>
                        · <?php echo (int) $item['exigencia']; ?> <?php echo htmlspecialchars($unidade, ENT_QUOTES, 'UTF-8'); ?>
                    <?php endif; ?>
                    <?php if (!empty($item['tipo_atividade_nome'])): ?>
                        (<?php echo htmlspecialchars($item['tipo_atividade_nome'], ENT_QUOTES, 'UTF-8'); ?>)
                    <?php endif; ?>
                    <?php if ($tipo === 'perfil_campos'): ?>
                        <?php
                        $rotulosCampos = [];
                        foreach (\App\Services\BonusApuracaoService::camposDoBonus($item) as $campoExigido) {
                            $rotulosCampos[] = \App\Services\BonusApuracaoService::CAMPOS_PERFIL_ROTULOS[$campoExigido];
                        }
                        ?>
                        (<?php echo htmlspecialchars(implode(', ', $rotulosCampos), ENT_QUOTES, 'UTF-8'); ?>)
                    <?php endif; ?>
                    · <?php echo (int) $item['pontos']; ?> <?php echo (int) $item['pontos'] === 1 ? 'ponto' : 'pontos'; ?>
                    · <?php echo (int) $item['total_creditos']; ?> <?php echo (int) $item['total_creditos'] === 1 ? 'pessoa já ganhou' : 'pessoas já ganharam'; ?>
                </span>
                <?php if (!empty($item['descricao'])): ?>
                    <br><small style="color:#555;"><?php echo htmlspecialchars($item['descricao'], ENT_QUOTES, 'UTF-8'); ?></small>
                <?php endif; ?>
            </div>
            <?php if ($podeEditar): ?>
            <div class="acoes-icones">
                <a href="<?php echo url('bonus/editar/' . (int) $evento['id'] . '/' . (int) $item['id']); ?>" class="btn-icone" title="Editar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </a>
                <?php if ((int) $item['total_creditos'] > 0): ?>
                <a href="<?php echo url('bonus/exportar/' . (int) $evento['id'] . '/' . (int) $item['id']); ?>" class="btn-icone" title="Exportar quem ganhou">⤓</a>
                <?php endif; ?>
                <form method="post" action="<?php echo url('bonus/remover/' . (int) $evento['id'] . '/' . (int) $item['id']); ?>" style="display:inline;" onsubmit="return confirm('Remover este bônus? Se ele já concedeu pontos, será apenas desativado.');"><?= campoCsrf() ?>
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
            </div>
            <div class="reordenar-botoes">
                <button type="button" class="btn-icone" data-mover="cima" aria-label="Mover para cima" <?php echo $indice === 0 ? 'disabled' : ''; ?>>▲</button>
                <button type="button" class="btn-icone" data-mover="baixo" aria-label="Mover para baixo" <?php echo $indice === count($bonus) - 1 ? 'disabled' : ''; ?>>▼</button>
            </div>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
