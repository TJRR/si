<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Submissao enviada com sucesso</h1>

<p>Sua submissao (numero <?php echo (int) $submissao['id']; ?>) foi recebida.</p>
