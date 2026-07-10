<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="grupo-participantes-linha" style="border:1px solid #ccc; padding:0.5em; margin-bottom:0.5em;">
    <label>Nome: <input type="text" name="campos[<?php echo $campoId; ?>][<?php echo $indice; ?>][nome]"></label><br>
    <label>CPF: <input type="text" name="campos[<?php echo $campoId; ?>][<?php echo $indice; ?>][cpf]" placeholder="000.000.000-00"></label><br>
    <label>E-mail: <input type="email" name="campos[<?php echo $campoId; ?>][<?php echo $indice; ?>][email]"></label><br>
    <label>Telefone: <input type="text" name="campos[<?php echo $campoId; ?>][<?php echo $indice; ?>][telefone]"></label><br>
    <label>Vínculo/Profissão: <input type="text" name="campos[<?php echo $campoId; ?>][<?php echo $indice; ?>][vinculo_profissao]"></label><br>
    <button type="button" class="grupo-participantes-remover">Remover</button>
</div>
