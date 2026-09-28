<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
$esc = function ($texto) {
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
};
?>
<div class="pagina-titulo-acoes">
    <h1>Editar comissão: <?php echo $esc($evento['nome']); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('anaisMontagem/index/' . (int) $evento['id']); ?>#bloco-comissoes" class="btn-voltar">Voltar</a>
    </div>
</div>

<p style="color:#555;font-size:0.9em;">O nome sai como título da comissão nas páginas iniciais do volume dos Anais. Os membros são editados na tela Montagem dos Anais, um a um.</p>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo $esc($erro); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('anaisMontagem/editarComissao/' . (int) $evento['id'] . '/' . (int) $comissao['id']); ?>"><?= campoCsrf() ?>
    <label>Nome da comissão:
        <input type="text" name="nome" required maxlength="<?php echo (int) \App\Services\EventoAnaisMontagemService::NOME_COMISSAO_MAXIMO; ?>" value="<?php echo $esc($comissao['nome']); ?>">
    </label>
    <div class="form-acoes">
        <button type="submit">Salvar comissão</button>
    </div>
</form>
