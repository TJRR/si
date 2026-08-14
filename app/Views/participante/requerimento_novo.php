<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Novo requerimento — <?php echo htmlspecialchars($modelo['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<div class="admin-card">
    <p><?php echo nl2br(htmlspecialchars($modelo['finalidade'], ENT_QUOTES, 'UTF-8')); ?></p>
</div>

<form method="post" action="<?php echo url('requerimento/gerarPdf/' . (int) $modelo['id']); ?>">
    <label>Necessidade (descrever brevemente):
        <textarea name="necessidade" rows="5" required></textarea>
    </label><br>

    <p>Ao clicar em "Gerar PDF", os demais campos do documento são preenchidos automaticamente com os dados já cadastrados da sua equipe. Você poderá baixar o PDF sem assinatura, assiná-lo por conta própria no <strong>gov.br</strong> e depois voltar aqui para enviar o documento assinado.</p>

    <div class="form-acoes">
        <a href="<?php echo url('requerimento/index'); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Gerar PDF</button>
    </div>
</form>
