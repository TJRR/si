<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Representante de estande</h1>

<section class="admin-card">
    <p>Você não representa nenhum estande no momento. Se acha que isso está errado, fale com a organização do evento.</p>
    <?php if ($temInscricaoEvento): ?>
        <p><a href="<?php echo url('eventoApp/index'); ?>" class="btn-acao">Abrir o aplicativo do evento</a></p>
    <?php endif; ?>
    <p><a href="<?php echo url('auth/logout'); ?>" class="btn-voltar">Sair</a></p>
</section>
