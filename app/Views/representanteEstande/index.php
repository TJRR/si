<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Meus estandes</h1>

<p>Você representa um estande em cada um dos eventos abaixo. Escolha o estande para ver as visitas, atualizar os dados ou imprimir o cartaz.</p>

<?php foreach ($estandes as $estande): ?>
    <section class="admin-card">
        <p>
            <strong><?php echo htmlspecialchars($estande['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
            <br>
            <small><?php echo htmlspecialchars($estande['evento_nome'], ENT_QUOTES, 'UTF-8'); ?></small>
        </p>
        <p>
            <span class="status-pill <?php echo $estande['ativo'] ? 'verde' : 'vermelho'; ?>"><?php echo $estande['ativo'] ? 'Recebendo visitas' : 'Inativo: não recebe visitas'; ?></span>
            <?php echo (int) $estande['total_visitas']; ?> <?php echo (int) $estande['total_visitas'] === 1 ? 'visita registrada' : 'visitas registradas'; ?>
        </p>
        <p><a href="<?php echo url('representanteEstande/estande/' . (int) $estande['id']); ?>" class="btn-acao">Abrir estande</a></p>
    </section>
<?php endforeach; ?>
