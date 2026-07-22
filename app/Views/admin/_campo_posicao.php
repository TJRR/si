<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Partial reaproveitavel de posicionamento em grade 3x3 (Fase 19,
 * #84 v5) - mesmo mecanismo do LG Conecta (_campo-posicao.blade.php,
 * radio + `:checked` + span estilizado, sem JS nenhum). Inclusao:
 *   <?php $nome = 'cta_posicao'; $valor = $banner['cta_posicao']; $rotulo = 'Posição do botão'; ?>
 *   <?php include __DIR__ . '/../_campo_posicao.php'; ?>
 * $nome e $valor sao obrigatorios; $rotulo e' opcional. Reaproveita o
 * mesmo vocabulario de 9 posicoes ja usado em
 * BannerRepository::CTA_POSICOES, sem duplicar a lista.
 */
$posicaoId = 'pos-' . preg_replace('/[^a-z0-9]/', '', strtolower($nome)) . '-' . mt_rand(1000, 9999);
$posicaoAtual = isset($valor) ? (string) $valor : 'centro_centro';
$posicoesRotulos = [
    'superior_esquerda' => 'Superior esquerda', 'superior_centro' => 'Superior centro', 'superior_direita' => 'Superior direita',
    'centro_esquerda' => 'Centro esquerda', 'centro_centro' => 'Centralizado', 'centro_direita' => 'Centro direita',
    'inferior_esquerda' => 'Inferior esquerda', 'inferior_centro' => 'Inferior centro', 'inferior_direita' => 'Inferior direita',
];
?>
<div class="campo-posicao">
    <?php if (!empty($rotulo)): ?>
        <span class="campo-posicao-rotulo" id="lbl-<?php echo $posicaoId; ?>"><?php echo htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8'); ?></span>
    <?php endif; ?>
    <div class="campo-posicao-grade" role="radiogroup" aria-labelledby="lbl-<?php echo $posicaoId; ?>">
        <?php foreach ($posicoesRotulos as $valorOpcao => $rotuloOpcao): ?>
            <label class="campo-posicao-opcao" title="<?php echo htmlspecialchars($rotuloOpcao, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="radio" name="<?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo $valorOpcao; ?>" <?php echo $posicaoAtual === $valorOpcao ? 'checked' : ''; ?> aria-label="<?php echo htmlspecialchars($rotuloOpcao, ENT_QUOTES, 'UTF-8'); ?>">
                <span aria-hidden="true"></span>
            </label>
        <?php endforeach; ?>
    </div>
</div>
