<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Bem-vindo, <?php echo htmlspecialchars(\App\Core\Auth::nome(), ENT_QUOTES, 'UTF-8'); ?></h1>

<div class="admin-dashboard-cards">
    <div class="admin-stat-card">
        <span class="admin-stat-numero"><?php echo (int) $totalEquipes; ?></span>
        <span class="admin-stat-rotulo">Equipes importadas</span>
    </div>
    <div class="admin-stat-card">
        <span class="admin-stat-numero"><?php echo (int) $totalCadastrosPendentes; ?></span>
        <span class="admin-stat-rotulo">Cadastros pendentes</span>
    </div>
    <div class="admin-stat-card">
        <span class="admin-stat-numero"><?php echo (int) $totalConcursosAtivos; ?></span>
        <span class="admin-stat-rotulo">Concursos ativos</span>
    </div>
</div>
