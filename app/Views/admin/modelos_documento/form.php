<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1><?php echo $modelo === null ? 'Novo modelo de documento' : 'Editar modelo de documento'; ?> — <?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<p><a href="<?php echo url('modelosDocumento/index/' . (int) $etapa['id']); ?>" class="btn-voltar">Voltar</a></p>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo $modelo === null ? url('modelosDocumento/novo/' . (int) $etapa['id']) : url('modelosDocumento/editar/' . (int) $modelo['id']); ?>"><?= campoCsrf() ?>
    <label>Nome do modelo:
        <input type="text" name="nome" required value="<?php echo htmlspecialchars($modelo !== null ? $modelo['nome'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Finalidade (explica ao líder quando/por que usar este modelo):
        <textarea name="finalidade" rows="8" style="min-height:10rem; width:100%; box-sizing:border-box;" required><?php echo htmlspecialchars($modelo !== null ? $modelo['finalidade'] : '', ENT_QUOTES, 'UTF-8'); ?></textarea>
    </label><br>

    <label>
        <input type="checkbox" name="ativo" value="1" <?php echo ($modelo === null || $modelo['ativo']) ? 'checked' : ''; ?>>
        Ativo (visível para os líderes)
    </label><br>

    <div class="modelo-corpo-wrapper">
        <div class="modelo-dicionario-area">
            <button type="button" class="modelo-dicionario-botao" title="Palavras-chave disponíveis" aria-label="Palavras-chave disponíveis">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                </svg>
            </button>
            <div class="modelo-dicionario-painel">
                <h3>Palavras-chave disponíveis</h3>
                <dl>
                    <?php foreach (\App\Services\ModeloDocumentoService::PALAVRAS_CHAVE as $chavePalavra => $descricaoPalavra): ?>
                        <dt><code>[[<?php echo htmlspecialchars($chavePalavra, ENT_QUOTES, 'UTF-8'); ?>]]</code></dt>
                        <dd><?php echo htmlspecialchars($descricaoPalavra, ENT_QUOTES, 'UTF-8'); ?></dd>
                    <?php endforeach; ?>
                </dl>
            </div>
        </div>
        <?php
            $nome = 'corpo_html';
            $valor = $modelo !== null ? $modelo['corpo_html'] : '';
            $rotulo = 'Corpo do documento';
            $mostrarPalavrasChave = true;
            include __DIR__ . '/../_editor_rico.php';
        ?>
    </div>

    <div class="form-acoes">
        <a href="<?php echo url('modelosDocumento/index/' . (int) $etapa['id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>

<script src="<?php echo config('base_path'); ?>/assets/js/modelo-documento-dicionario.js"></script>
