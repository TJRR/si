<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Partial reaproveitavel de campo de cor com hex editavel (Fase 19,
 * #84 v5) - mesmo mecanismo do LG Conecta (_campo-cor.blade.php),
 * portado pra vanilla JS (assets/js/campo-cor.js). Inclusao:
 *   <?php $nome = 'cor_fundo'; $valor = $slide['cor_fundo']; $rotulo = 'Cor de fundo'; ?>
 *   <?php include __DIR__ . '/../_campo_cor.php'; ?>
 * $nome e $valor sao obrigatorios; $rotulo e' opcional; $padrao (cor
 * usada ao clicar "sem cor", default #F38123) e $permiteVazio (default
 * true - quando false, nunca mostra "sem"/"remover", o campo sempre tem
 * uma cor) sao opcionais.
 */
$corId = 'cor-' . preg_replace('/[^a-z0-9]/', '', strtolower($nome)) . '-' . mt_rand(1000, 9999);
$corAtual = isset($valor) ? strtoupper((string) $valor) : '';
$corPadrao = isset($padrao) ? $padrao : '#F38123';
$permiteVazio = isset($permiteVazio) ? (bool) $permiteVazio : true;
$temCor = $corAtual !== '' || !$permiteVazio;
$corPicker = $corAtual !== '' ? $corAtual : $corPadrao;
?>
<div class="campo-cor" data-campo-cor data-cor-padrao="<?php echo htmlspecialchars($corPadrao, ENT_QUOTES, 'UTF-8'); ?>">
    <?php if (!empty($rotulo)): ?>
        <label class="campo-cor-rotulo" for="<?php echo $corId; ?>"><?php echo htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8'); ?></label>
    <?php endif; ?>
    <div class="campo-cor-controles">
        <?php if ($permiteVazio): ?>
            <button type="button" class="campo-cor-sem" data-cor-sem <?php echo $temCor ? 'hidden' : ''; ?>>sem</button>
        <?php endif; ?>
        <input type="color" class="campo-cor-picker" data-cor-picker value="<?php echo htmlspecialchars($corPicker, ENT_QUOTES, 'UTF-8'); ?>" <?php echo !$temCor ? 'hidden' : ''; ?>>
        <input type="text" id="<?php echo $corId; ?>" class="campo-cor-texto" data-cor-texto maxlength="7" placeholder="sem cor" value="<?php echo htmlspecialchars($corAtual, ENT_QUOTES, 'UTF-8'); ?>">
        <?php if ($permiteVazio): ?>
            <button type="button" class="campo-cor-limpar" data-cor-limpar aria-label="Remover cor" <?php echo !$temCor ? 'hidden' : ''; ?>>&times;</button>
        <?php endif; ?>
        <input type="hidden" name="<?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?>" data-cor-valor value="<?php echo htmlspecialchars($corAtual, ENT_QUOTES, 'UTF-8'); ?>">
    </div>
</div>
