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
    <h1>Editar membro de comissão: <?php echo $esc($evento['nome']); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('anaisMontagem/index/' . (int) $evento['id']); ?>#bloco-comissoes" class="btn-voltar">Voltar</a>
    </div>
</div>

<p style="color:#555;font-size:0.9em;">Comissão: <strong><?php echo $esc($membro['comissao_nome']); ?></strong>. No volume dos Anais, o membro sai numa linha, com o nome, a função e a instituição separados por vírgula. Para trocar o membro de comissão, remova-o e inclua de novo na comissão certa.</p>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo $esc($erro); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('anaisMontagem/editarMembro/' . (int) $evento['id'] . '/' . (int) $membro['id']); ?>"><?= campoCsrf() ?>
    <label>Nome:
        <input type="text" name="nome" required maxlength="<?php echo (int) \App\Services\EventoAnaisMontagemService::NOME_MEMBRO_MAXIMO; ?>" value="<?php echo $esc($membro['nome']); ?>">
    </label>
    <label>Função (opcional):
        <input type="text" name="funcao" maxlength="<?php echo (int) \App\Services\EventoAnaisMontagemService::FUNCAO_MAXIMA; ?>" value="<?php echo $esc($membro['funcao']); ?>">
    </label>
    <label>Instituição (opcional):
        <input type="text" name="instituicao" maxlength="<?php echo (int) \App\Services\EventoAnaisMontagemService::INSTITUICAO_MAXIMA; ?>" value="<?php echo $esc($membro['instituicao']); ?>">
    </label>
    <div class="form-acoes">
        <button type="submit">Salvar membro</button>
    </div>
</form>
