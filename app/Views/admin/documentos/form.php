<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Novo documento — <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('documentos/novo/' . (int) $concurso['id']); ?>" enctype="multipart/form-data">
    <label>Tipo:
        <select name="tipo" required>
            <?php $rotulosTipo = ['edital' => 'Edital', 'edital_simples' => 'Edital em linguagem simples', 'anexo' => 'Anexo', 'retificacao' => 'Retificação', 'resultado_final' => 'Resultado final', 'ata' => 'Ata']; ?>
            <?php foreach ($rotulosTipo as $valorOpcao => $rotuloOpcao): ?>
                <option value="<?php echo $valorOpcao; ?>"><?php echo $rotuloOpcao; ?></option>
            <?php endforeach; ?>
        </select>
    </label><br>

    <label>Trilha (opcional — deixe em branco se o documento vale para todo o concurso):
        <select name="trilha_id">
            <option value="">— Todo o concurso —</option>
            <?php foreach ($trilhas as $trilha): ?>
                <option value="<?php echo (int) $trilha['id']; ?>"><?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
        </select>
    </label><br>

    <label>Título:
        <input type="text" name="titulo" required placeholder="Ex.: Edital de Inscrição — Trilha Interna">
    </label><br>
    <p style="color:var(--cor-texto-suave);font-size:.85rem;">Se já existir um documento com o mesmo tipo + título, este upload vira uma nova versão dele automaticamente.</p>

    <label>Arquivo (PDF, até 15MB):
        <input type="file" name="arquivo" accept="application/pdf" required>
    </label>

    <div class="form-acoes">
        <a href="<?php echo url('documentos/index/' . (int) $concurso['id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Enviar</button>
    </div>
</form>
