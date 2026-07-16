<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
// Fase 17 (Bug 2): $participanteExistente vem do loop de pre-preenchimento em
// formulario.php - no <template> do fim do arquivo, que gera linhas novas
// via JS, nunca ha' dado existente.
$participanteExistente = isset($participanteExistente) && is_array($participanteExistente) ? $participanteExistente : null;
?>
<div class="grupo-participantes-linha" style="border:1px solid #ccc; padding:0.5em; margin-bottom:0.5em;">
    <label>Nome: <input type="text" name="campos[<?php echo $campoId; ?>][<?php echo $indice; ?>][nome]" value="<?php echo $participanteExistente !== null ? htmlspecialchars($participanteExistente['nome'], ENT_QUOTES, 'UTF-8') : ''; ?>"></label><br>
    <label>CPF: <input type="text" name="campos[<?php echo $campoId; ?>][<?php echo $indice; ?>][cpf]" class="campo-cpf-validar" placeholder="000.000.000-00" value="<?php echo $participanteExistente !== null ? htmlspecialchars((string) $participanteExistente['cpf'], ENT_QUOTES, 'UTF-8') : ''; ?>"></label><br>
    <label>E-mail: <input type="email" name="campos[<?php echo $campoId; ?>][<?php echo $indice; ?>][email]" value="<?php echo $participanteExistente !== null ? htmlspecialchars((string) $participanteExistente['email'], ENT_QUOTES, 'UTF-8') : ''; ?>"></label><br>
    <label>Telefone: <input type="text" name="campos[<?php echo $campoId; ?>][<?php echo $indice; ?>][telefone]" value="<?php echo $participanteExistente !== null ? htmlspecialchars((string) $participanteExistente['telefone'], ENT_QUOTES, 'UTF-8') : ''; ?>"></label><br>
    <label>Vínculo/Profissão: <input type="text" name="campos[<?php echo $campoId; ?>][<?php echo $indice; ?>][vinculo_profissao]" value="<?php echo $participanteExistente !== null ? htmlspecialchars((string) $participanteExistente['vinculo_profissao'], ENT_QUOTES, 'UTF-8') : ''; ?>"></label><br>
    <button type="button" class="grupo-participantes-remover">Remover</button>
</div>
