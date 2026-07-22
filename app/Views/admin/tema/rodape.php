<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Rodapé</h1>
    <div class="pagina-titulo-botoes">
        <button type="submit" form="form-rodape">Salvar</button>
    </div>
</div>

<?php if (!empty($_SESSION['flash'])): ?>
    <p style="color:red;"><?php echo htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['flash']); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('tema/rodape'); ?>" enctype="multipart/form-data" id="form-rodape">
    <fieldset>
        <legend>Logo do rodapé (opcional)</legend>
        <p>Se enviada, substitui a logo padrão no rodapé (útil pra uma versão em cor diferente, sem precisar de tratamento de imagem toda vez). Sem imagem, o rodapé usa a mesma logo do cabeçalho.</p>
        <?php if (!empty($configuracaoVisual['rodape_logo_path'])): ?>
            <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $configuracaoVisual['rodape_logo_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="Logo atual do rodapé" style="max-width:200px;display:block;margin-bottom:.5rem;background:#333;padding:.5rem;">
        <?php endif; ?>
        <label>
            Trocar logo do rodapé:<br>
            <input type="file" name="rodape_logo" accept="image/*">
        </label>
    </fieldset>

    <fieldset>
        <legend>Atalhos de navegação no rodapé</legend>
        <p>Escolha quais seções aparecem na coluna "Navegação" do rodapé — independente do que aparece no menu do cabeçalho. "Sobre o Prêmio", "Premiação" e blocos livres (como "Mentorias Opcionais") têm essa opção na própria tela de Blocos de conteúdo.</p>
        <label>
            <input type="checkbox" name="rodape_mostrar_trilhas" value="1" <?php echo (!$configuracaoVisual || $configuracaoVisual['rodape_mostrar_trilhas']) ? 'checked' : ''; ?>>
            Trilhas
        </label><br>
        <label>
            <input type="checkbox" name="rodape_mostrar_cronograma" value="1" <?php echo (!$configuracaoVisual || $configuracaoVisual['rodape_mostrar_cronograma']) ? 'checked' : ''; ?>>
            Cronograma
        </label><br>
        <label>
            <input type="checkbox" name="rodape_mostrar_desafios" value="1" <?php echo (!$configuracaoVisual || $configuracaoVisual['rodape_mostrar_desafios']) ? 'checked' : ''; ?>>
            Desafios
        </label><br>
        <label>
            <input type="checkbox" name="rodape_mostrar_contato" value="1" <?php echo (!$configuracaoVisual || $configuracaoVisual['rodape_mostrar_contato']) ? 'checked' : ''; ?>>
            Contato
        </label>
    </fieldset>
</form>
