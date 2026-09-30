<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
// Reabertura da Fase 51 (item 1): tela do fluxo do Evento, com a logo do
// Evento e o "Voltar" levando a pagina do proprio evento, nao a home do
// Concurso.
$logoSrc = logoAtual(true);
?>
<div class="guest-card">
    <img src="<?php echo htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?>" class="guest-logo">

    <h1 class="guest-titulo">Criar cadastro</h1>
    <?php if (!empty($paraSubmissao)): ?>
        <?php /* Fase 54: veio do botao de enviar trabalho; depois do
        cadastro, segue direto para o formulario de submissao. */ ?>
        <p class="guest-subtitulo">Crie sua conta para enviar o seu trabalho em <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?>. Logo depois do cadastro, você volta ao formulário de submissão, que recupera o que este navegador tiver guardado do seu preenchimento.</p>
    <?php else: ?>
        <p class="guest-subtitulo">Cadastre-se para se inscrever em <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?>.</p>
    <?php endif; ?>

    <?php if (!empty($erro)): ?>
        <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form method="post" action="<?php echo url('eventoInscricao/cadastrar/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
        <label>
            Nome
            <input type="text" name="nome" required>
        </label>
        <label>
            E-mail
            <input type="email" name="email" required autocomplete="username">
        </label>
        <label>
            Senha (ao menos 8 caracteres)
            <input type="password" name="senha" required minlength="8" autocomplete="new-password">
        </label>
        <button type="submit" class="btn btn-bordered">Cadastrar</button>
    </form>

    <?php if (!empty($paraSubmissao)): ?>
        <p class="guest-cadastro">Já tem conta? <a href="<?php echo url('auth/loginEvento/' . (int) $evento['id']); ?>">Entrar</a> (e-mail e senha ou conta Google). Depois de entrar, você também vai direto para o formulário de submissão.</p>
    <?php endif; ?>

    <p class="guest-cadastro"><a href="<?php echo url('eventoInscricao/index/' . (int) $evento['id']); ?>">Voltar</a></p>
</div>

<a href="<?php echo htmlspecialchars(urlPaginaEvento($evento['id']), ENT_QUOTES, 'UTF-8'); ?>" class="guest-voltar">&larr; Voltar à página do evento</a>
<p class="guest-copyright">&copy; <?php echo date('Y'); ?> Poder Judiciário de Roraima</p>
