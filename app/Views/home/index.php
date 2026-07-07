<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Bem-vindo, <?php echo htmlspecialchars(\App\Core\Auth::nome(), ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('usuarios/index'); ?>">Cadastros pendentes</a></p>
<p><a href="<?php echo url('concursos/index'); ?>">Concursos</a></p>
<p><a href="<?php echo url('formularios/index'); ?>">Formularios Dinamicos</a></p>
<p><a href="<?php echo url('auth/logout'); ?>">Sair</a></p>
