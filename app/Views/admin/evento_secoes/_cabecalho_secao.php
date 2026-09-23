<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Título da tela e botão de voltar, iguais em todo formulário de componente.
 * Espera $evento, $rotuloTipo.
 */
?>
<div class="pagina-titulo-acoes">
    <h1><?php echo htmlspecialchars($rotuloTipo, ENT_QUOTES, 'UTF-8'); ?>: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('eventoSecoes/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>
