<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>

<div class="site-page">
    <header class="site-header">
        <div class="site-header-inner">
            <img src="<?php echo htmlspecialchars($logoAdminSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Prêmio de Inovação TJRR" class="site-logo">
            <nav class="site-nav">
                <?php if (isset($ajudaHtml) && $ajudaHtml !== null): ?>
                <button type="button" class="site-header-icone" title="Ajuda desta página" aria-label="Ajuda desta página" data-ajuda-titulo="<?php echo htmlspecialchars('Ajuda — ' . (string) $ajudaTitulo, ENT_QUOTES, 'UTF-8'); ?>" onclick="abrirModal(this.dataset.ajudaTitulo, document.getElementById('ajuda-painel-fonte').innerHTML)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </button>
                <?php endif; ?>
                <a href="<?php echo url('home/index'); ?>" class="btn">Voltar ao início</a>
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

<form method="post" action="<?php echo url('inscricao/enviar/' . (int) $preparo['etapa']['id']); ?>"><?= campoCsrf() ?>
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
                    <input type="text" name="campos[<?php echo $campoId; ?>]" class="campo-cpf-validar" placeholder="000.000.000-00" <?php echo $campo['obrigatorio'] ? 'required' : ''; ?>>
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

<script src="<?php echo config('base_path'); ?>/assets/js/cpf-validador.js"></script>
