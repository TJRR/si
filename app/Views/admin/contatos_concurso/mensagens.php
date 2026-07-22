<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Mensagens recebidas</h1>
</div>
<p><a href="<?php echo url('contatosConcurso/index'); ?>" class="btn-voltar">Voltar ao contato</a></p>

<?php if (empty($mensagens)): ?>
    <p>Nenhuma mensagem recebida ainda.</p>
<?php else: ?>
    <div class="tabela-scroll">
        <table>
            <tr><th>Data</th><th>Nome</th><th>E-mail</th><th>Mensagem</th></tr>
            <?php foreach ($mensagens as $mensagem): ?>
            <tr>
                <td><?php echo htmlspecialchars(formatarData($mensagem['criado_em']), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($mensagem['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><a href="mailto:<?php echo htmlspecialchars($mensagem['email'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($mensagem['email'], ENT_QUOTES, 'UTF-8'); ?></a></td>
                <td><?php echo nl2br(htmlspecialchars($mensagem['mensagem'], ENT_QUOTES, 'UTF-8')); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>
