<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Novo documento: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('eventoDocumentos/novo/' . (int) $evento['id']); ?>" enctype="multipart/form-data"><?= campoCsrf() ?>
    <label>Tipo:
        <select name="tipo" required>
            <?php foreach (\App\Repositories\EventoDocumentoRepository::ROTULOS_TIPO as $valorOpcao => $rotuloOpcao): ?>
                <option value="<?php echo $valorOpcao; ?>" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] === $valorOpcao) ? 'selected' : ''; ?>><?php echo $rotuloOpcao; ?></option>
            <?php endforeach; ?>
        </select>
    </label><br>

    <label>Título:
        <input type="text" name="titulo" required maxlength="200" placeholder="Ex.: Edital de submissão de trabalhos" value="<?php echo htmlspecialchars(isset($_POST['titulo']) ? (string) $_POST['titulo'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>
    <p style="color:var(--cor-texto-suave);font-size:.85rem;">Se já existir um documento com o mesmo tipo e título, este envio vira uma nova versão dele automaticamente.</p>

    <label>Arquivo (PDF ou documento do Word ou do LibreOffice, até <?php echo \App\Services\ArquivoService::limiteMaximoMB(); ?>MB):
        <input type="file" name="arquivo" accept=".pdf,.doc,.docx,.odt,application/pdf" required>
    </label>

    <div class="form-acoes">
        <a href="<?php echo url('eventoDocumentos/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Enviar</button>
    </div>
</form>
