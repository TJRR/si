<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>

<div class="site-page">
    <header class="site-header">
        <div class="site-header-inner">
            <img src="<?php echo htmlspecialchars($logoAdminSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Prêmio de Inovação TJRR" class="site-logo">
            <nav class="site-nav">
                <a href="<?php echo url('home/index'); ?>">Voltar ao início</a>
            </nav>
        </div>
    </header>

    <div class="site-form-page">
<?php if ($erroGeral !== null && $preparo === null): ?>
    <h1>Inscrição indisponível</h1>
    <p style="color:red;"><?php echo htmlspecialchars($erroGeral, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
</div>
    <?php return; ?>
<?php endif; ?>

<h1><?php echo htmlspecialchars($preparo['formulario']['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if (!empty($preparo['formulario']['descricao'])): ?>
    <p><?php echo nl2br(htmlspecialchars($preparo['formulario']['descricao'], ENT_QUOTES, 'UTF-8')); ?></p>
<?php endif; ?>

<?php if ($erroGeral !== null): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erroGeral, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('inscricao/enviar/' . (int) $preparo['etapa']['id']); ?>">
    <?php foreach ($preparo['campos'] as $campo): ?>
        <?php
        $campoId = (int) $campo['id'];
        $temErro = isset($erros[$campoId]);
        ?>
        <fieldset style="margin-bottom:1em;">
            <label>
                <?php echo htmlspecialchars($campo['rotulo'], ENT_QUOTES, 'UTF-8'); ?>
                <?php echo $campo['obrigatorio'] ? '*' : ''; ?>

                <?php if ($campo['tipo'] === 'cpf'): ?>
                    <input type="text" name="campos[<?php echo $campoId; ?>]" placeholder="000.000.000-00" <?php echo $campo['obrigatorio'] ? 'required' : ''; ?>>
                <?php elseif ($campo['tipo'] === 'email'): ?>
                    <input type="email" name="campos[<?php echo $campoId; ?>]" <?php echo $campo['obrigatorio'] ? 'required' : ''; ?>>
                <?php elseif ($campo['tipo'] === 'telefone'): ?>
                    <input type="text" name="campos[<?php echo $campoId; ?>]" placeholder="(00) 00000-0000" <?php echo $campo['obrigatorio'] ? 'required' : ''; ?>>
                <?php else: ?>
                    <input type="text" name="campos[<?php echo $campoId; ?>]" <?php echo $campo['obrigatorio'] ? 'required' : ''; ?>>
                <?php endif; ?>
            </label>

            <?php if ($temErro): ?>
                <br><span style="color:red;"><?php echo htmlspecialchars($erros[$campoId], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
        </fieldset>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-bordered">Enviar inscrição</button>
</form>
    </div>
</div>
