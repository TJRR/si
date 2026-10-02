<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 58: extrato de pontos de uma pessoa. Conexões aparecem só em total:
 * a administração não lista quem se conectou com quem (decisão registrada
 * em ConexaoAdminController, Fase 55).
 */
$rotulosOrigem = [
    'presenca' => 'Presença em atividades',
    'competicoes' => 'Competições',
    'conexoes' => 'Conexões',
    'estandes' => 'Visitas a estandes',
    'divulgacao' => 'Divulgação',
    'bonus' => 'Bônus e ações do participante',
];
?>
<div class="pagina-titulo-acoes">
    <h1>Extrato de pontos: <?php echo htmlspecialchars($participante !== null ? $participante['nome'] : '', ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('gamificacao/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<?php if ($soma === null): ?>
    <p class="status-pill vermelho">O total está indisponível no momento. Tente de novo em alguns instantes.</p>
<?php else: ?>
    <div class="admin-card">
        <p><strong>Total válido: <?php echo (int) $soma['total']; ?> pontos</strong></p>
        <ul>
            <?php foreach ($rotulosOrigem as $origem => $rotulo): ?>
                <li><?php echo htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8'); ?>: <?php echo (int) $soma['por_origem'][$origem]; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<h2>Presença em atividades</h2>
<?php if (empty($presencas)): ?>
    <p>Nenhuma presença pontuada.</p>
<?php else: ?>
    <ul>
        <?php foreach ($presencas as $presenca): ?>
            <li>
                <?php echo htmlspecialchars($presenca['atividade_nome'], ENT_QUOTES, 'UTF-8'); ?>:
                <?php echo (int) $presenca['pontos_presenca']; ?>
                <?php if ((int) $presenca['pontos_pontualidade'] > 0): ?>
                    + <?php echo (int) $presenca['pontos_pontualidade']; ?> de pontualidade
                <?php endif; ?>
                <?php if ($presenca['anulado_em'] !== null): ?>
                    <span class="status-pill vermelho">Anulados</span>
                    <small><?php echo htmlspecialchars((string) $presenca['motivo_anulacao'], ENT_QUOTES, 'UTF-8'); ?></small>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<h2>Competições</h2>
<?php if (empty($participacoes)): ?>
    <p>Nenhuma participação registrada.</p>
<?php else: ?>
    <ul>
        <?php foreach ($participacoes as $participacao): ?>
            <li>
                <?php echo htmlspecialchars($participacao['competicao_nome'], ENT_QUOTES, 'UTF-8'); ?>:
                <?php echo (int) $participacao['pontos_creditados']; ?> pontos
                <?php if ($participacao['anulado_em'] !== null): ?>
                    <span class="status-pill vermelho">Anulada</span>
                    <small><?php echo htmlspecialchars((string) $participacao['motivo_anulacao'], ENT_QUOTES, 'UTF-8'); ?></small>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<h2>Bônus e ações do participante</h2>
<?php if (empty($bonus['por_bonus'])): ?>
    <p>Nenhum bônus concedido.</p>
<?php else: ?>
    <ul>
        <?php foreach ($bonus['por_bonus'] as $bonusId => $credito): ?>
            <li>
                <?php echo htmlspecialchars(isset($nomesBonus[$bonusId]) ? $nomesBonus[$bonusId] : 'Bônus', ENT_QUOTES, 'UTF-8'); ?>:
                <?php echo (int) $credito['pontos']; ?> pontos
                <?php if ($credito['anulado_em'] !== null): ?>
                    <span class="status-pill vermelho"><?php echo $credito['anulado_por'] === null ? 'Anulado pelo sistema' : 'Anulado'; ?></span>
                    <small><?php echo htmlspecialchars((string) $credito['motivo_anulacao'], ENT_QUOTES, 'UTF-8'); ?></small>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<h2>Visitas a estandes</h2>
<?php if (empty($estandes['visitas'])): ?>
    <p>Nenhuma visita registrada.</p>
<?php else: ?>
    <ul>
        <?php foreach ($estandes['visitas'] as $visita): ?>
            <li><?php echo htmlspecialchars($visita['nome'], ENT_QUOTES, 'UTF-8'); ?>: <?php echo (int) $visita['pontos_creditados']; ?> pontos</li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<h2>Divulgação</h2>
<?php if (empty($divulgacao)): ?>
    <p>Nenhuma comprovação enviada.</p>
<?php else: ?>
    <ul>
        <?php foreach ($divulgacao as $comprovacao): ?>
            <li>
                <?php echo htmlspecialchars(\App\Repositories\DivulgacaoConfigRepository::rotuloDaRede($comprovacao['rede']), ENT_QUOTES, 'UTF-8'); ?>,
                <?php echo $comprovacao['tipo_acao'] === 'acompanhar' ? 'seguir canal' : 'publicação'; ?>:
                <?php echo (int) $comprovacao['pontos_creditados']; ?> pontos
                <?php if ($comprovacao['anulado_em'] !== null): ?>
                    <span class="status-pill vermelho">Anulada</span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<p style="color:#555;font-size:0.9em;">
    Conexões aparecem só em total. A lista de quem se conectou com quem não é mostrada na administração.
</p>
