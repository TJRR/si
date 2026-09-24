<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Reabertura da Fase 51: um bloco de coautor do formulario de submissao,
 * usado duas vezes por trabalho/formulario.php - para cada coautor que
 * voltou preenchido depois de um erro ($posicao = posicao do bloco no
 * formulario, a mesma que o servidor devolve em indiceErro) e no modelo
 * (<template>) que o script clona ao adicionar um coautor ($posicao nulo).
 * Nome, CPF e e-mail sao obrigatorios (o bloco inteiro pode ser removido);
 * cargo e orgao de origem sao opcionais. Usa os auxiliares $v/$classeErro/
 * $mensagemErro/$focoErro e $rotulo definidos no inicio do formulario.
 */
$coautorValores = isset($coautorValores) && is_array($coautorValores) ? $coautorValores : [];
?>
<fieldset class="trabalho-coautor-item">
    <label class="<?php echo $posicao !== null ? $classeErro('coautor_nome', $posicao) : ''; ?>"><?php echo $rotulo('Nome completo', true); ?>
        <input type="text" name="coautor_nome[]" maxlength="150" required value="<?php echo $v($coautorValores, 'nome'); ?>"<?php echo $posicao !== null ? $focoErro('coautor_nome', $posicao) : ''; ?>>
        <?php echo $posicao !== null ? $mensagemErro('coautor_nome', $posicao) : ''; ?>
    </label>
    <label class="<?php echo $posicao !== null ? $classeErro('coautor_cpf', $posicao) : ''; ?>"><?php echo $rotulo('CPF', true); ?>
        <input type="text" name="coautor_cpf[]" maxlength="14" required class="campo-cpf-validar" value="<?php echo $v($coautorValores, 'cpf'); ?>"<?php echo $posicao !== null ? $focoErro('coautor_cpf', $posicao) : ''; ?>>
        <?php echo $posicao !== null ? $mensagemErro('coautor_cpf', $posicao) : ''; ?>
    </label>
    <label class="<?php echo $posicao !== null ? $classeErro('coautor_email', $posicao) : ''; ?>"><?php echo $rotulo('E-mail', true); ?>
        <input type="email" name="coautor_email[]" maxlength="150" required value="<?php echo $v($coautorValores, 'email'); ?>"<?php echo $posicao !== null ? $focoErro('coautor_email', $posicao) : ''; ?>>
        <?php echo $posicao !== null ? $mensagemErro('coautor_email', $posicao) : ''; ?>
    </label>
    <label><?php echo $rotulo('Cargo', false); ?>
        <input type="text" name="coautor_cargo[]" maxlength="150" value="<?php echo $v($coautorValores, 'cargo'); ?>">
    </label>
    <label><?php echo $rotulo('Órgão de origem', false); ?>
        <input type="text" name="coautor_orgao_origem[]" maxlength="150" value="<?php echo $v($coautorValores, 'orgao'); ?>">
    </label>
    <button type="button" class="trabalho-coautor-remover btn">Remover coautor</button>
</fieldset>
