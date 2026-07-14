<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1><?php echo htmlspecialchars($tituloPagina, ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php if (!empty($sucesso)): ?>
    <p style="color:green;"><?php echo htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo $actionUrl; ?>">
    <label>Nome:
        <input type="text" name="nome" required value="<?php echo htmlspecialchars($participante['nome'], ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>CPF (se corrigir, a inscrição volta para conferência do Suporte):
        <input type="text" name="cpf" class="campo-cpf-validar" placeholder="000.000.000-00" value="<?php echo htmlspecialchars((string) $participante['cpf'], ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Telefone:
        <input type="text" name="telefone" value="<?php echo htmlspecialchars((string) $participante['telefone'], ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>E-mail (login — não editável aqui):
        <input type="email" value="<?php echo htmlspecialchars((string) $participante['email'], ENT_QUOTES, 'UTF-8'); ?>" disabled>
    </label><br>

    <div class="form-acoes">
        <a href="<?php echo url('participante/index'); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>

<script src="<?php echo config('base_path'); ?>/assets/js/cpf-validador.js"></script>
