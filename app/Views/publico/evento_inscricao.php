<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>

<div class="site-page">
    <?php if (ehContextoApp()): ?>
    <?php
    /**
     * Fase 41 (correcao pos-teste de fumaca): quem acessa pelo celular, ou
     * ja' esta' navegando dentro do PWA instalado (mesmo no computador -
     * ver ehContextoApp(), app/helpers.php), e' o publico do aplicativo do
     * Evento, mesmo antes de ter conta - a aparencia de app comeca aqui,
     * nesta primeira tela, em vez de so' depois do login. No computador
     * (sem o app instalado) continua sendo o site institucional comum (ver
     * bloco else abaixo). $ehAppEvento (app/Views/layout.php) reconhece
     * esta mesma view + ehContextoApp() pra tambem ativar manifesto/service
     * worker/CSS do app aqui.
     */
    $eventoId = isset($evento) && $evento !== null ? $evento['id'] : null;
    $tituloTopo = $evento !== null ? $evento['nome'] : 'Semana de Inovação';
    require __DIR__ . '/../eventoApp/_app_bar.php';
    ?>
    <?php else: ?>
    <?php
    // Fase 50 (correcao): esta view e' exclusiva do Evento - $logoAdminSrc
    // (app/Views/layout.php) nunca era definida aqui (esta view nao entra
    // em $ehPaginaConvidado), e o texto alternativo fixo "Prêmio de
    // Inovação" era branding do Concurso numa tela que e' so' do Evento.
    // logoAtual(true) resolve a logo propria do Evento (com fallback pra
    // logo do site), mesmo padrao ja usado em EventoPublicoController.
    $logoDesktopEvento = logoAtual(true);
    $altLogoDesktopEvento = isset($evento) && $evento !== null ? $evento['nome'] : 'Semana de Inovação';
    ?>
    <header class="site-header">
        <div class="site-header-inner">
            <img src="<?php echo htmlspecialchars($logoDesktopEvento, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($altLogoDesktopEvento, ENT_QUOTES, 'UTF-8'); ?>" class="site-logo">
            <nav class="site-nav">
                <?php if (isset($ajudaHtml) && $ajudaHtml !== null): ?>
                <button type="button" class="site-header-icone" title="Ajuda desta página" aria-label="Ajuda desta página" data-ajuda-titulo="<?php echo htmlspecialchars('Ajuda: ' . (string) $ajudaTitulo, ENT_QUOTES, 'UTF-8'); ?>" onclick="abrirModal(this.dataset.ajudaTitulo, document.getElementById('ajuda-painel-fonte').innerHTML)">
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
    <?php endif; ?>

    <div class="site-form-page">
<?php if ($evento === null): ?>
    <h1>Semana de Inovação</h1>
    <p>Nenhum evento disponível para inscrição no momento.</p>
    </div>
</div>
    <?php return; ?>
<?php endif; ?>

<h1><?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if (!empty($evento['descricao'])): ?>
    <p><?php echo nl2br(htmlspecialchars($evento['descricao'], ENT_QUOTES, 'UTF-8')); ?></p>
<?php endif; ?>

<p>
    <?php echo htmlspecialchars(formatarData($evento['data_inicio']), ENT_QUOTES, 'UTF-8'); ?>
    a
    <?php echo htmlspecialchars(formatarData($evento['data_fim']), ENT_QUOTES, 'UTF-8'); ?>
</p>

<?php if (!$autenticado): ?>
    <p>Já é inscrito ou tem conta no sistema? Entre. Ainda não se cadastrou? Escolha uma opção de cadastro: qualquer conta já aprovada no sistema pode se inscrever.</p>

    <div class="site-botoes-empilhados">
        <h3>Cadastro</h3>
        <a href="<?php echo url('auth/google') . '&contexto=evento'; ?>" class="btn btn-bordered">Cadastrar com o Google</a>
        <a href="<?php echo url('eventoInscricao/cadastrar/' . (int) $evento['id']); ?>" class="btn btn-bordered">Cadastrar com e-mail e senha</a>

        <h3>Acesso</h3>
        <a href="<?php echo url('auth/google') . '&contexto=evento'; ?>" class="btn btn-bordered">Entrar com o Google</a>
        <a href="<?php echo url('auth/loginEvento'); ?>" class="btn btn-bordered">Entrar com e-mail e senha</a>
    </div>
    </div>
</div>
    <?php return; ?>
<?php endif; ?>

<?php if ($erroGeral !== null): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erroGeral, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('eventoInscricao/inscrever'); ?>"><?= campoCsrf() ?>
    <input type="hidden" name="evento_id" value="<?php echo (int) $evento['id']; ?>">
    <fieldset style="margin-bottom:1em;">
        <label>
            Documento *
            <input type="text" id="campo-documento" name="documento" value="<?php echo htmlspecialchars(isset($dados['documento']) ? $dados['documento'] : '', ENT_QUOTES, 'UTF-8'); ?>" required>
        </label>
        <?php if (isset($erros['documento'])): ?>
            <br><span style="color:red;"><?php echo htmlspecialchars($erros['documento'], ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>
    </fieldset>

    <?php foreach ($campos as $campo): ?>
        <?php
        $nomePost = 'campo_' . $campo['id'];
        $valorAtual = isset($dados[$nomePost]) ? $dados[$nomePost] : '';
        $config = $campo['config_json'] !== null ? json_decode($campo['config_json'], true) : null;
        $opcoes = $config !== null && isset($config['opcoes']) ? $config['opcoes'] : [];
        ?>
        <fieldset style="margin-bottom:1em;">
            <label>
                <?php echo htmlspecialchars($campo['rotulo'], ENT_QUOTES, 'UTF-8'); ?>
                <?php echo $campo['obrigatorio'] ? '*' : ''; ?>

                <?php if ($campo['tipo'] === 'lista_opcoes'): ?>
                    <select name="<?php echo $nomePost; ?>" data-rotulo-campo="<?php echo htmlspecialchars($campo['rotulo'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $campo['obrigatorio'] ? 'required' : ''; ?>>
                        <option value="">Selecione...</option>
                        <?php foreach ($opcoes as $opcao): ?>
                            <option value="<?php echo htmlspecialchars($opcao, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $valorAtual === $opcao ? 'selected' : ''; ?>><?php echo htmlspecialchars($opcao, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="text" name="<?php echo $nomePost; ?>" value="<?php echo htmlspecialchars($valorAtual, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $campo['obrigatorio'] ? 'required' : ''; ?>>
                <?php endif; ?>
            </label>

            <?php if (!empty($campo['texto_ajuda'])): ?>
                <br><small style="color:#555;"><?php echo htmlspecialchars($campo['texto_ajuda'], ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>

            <?php if (isset($erros[$nomePost])): ?>
                <br><span style="color:red;"><?php echo htmlspecialchars($erros[$nomePost], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
        </fieldset>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-bordered">Confirmar inscrição</button>
</form>
    </div>
</div>

<script src="<?php echo config('base_path'); ?>/assets/js/cpf-validador.js"></script>
<script src="<?php echo config('base_path'); ?>/assets/js/documento-tipo-mascara.js"></script>
