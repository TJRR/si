<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Avaliadores de Trabalhos: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('trabalhos/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<p style="color:#555;font-size:0.9em;">Avaliadores avulsos deste evento, convidados por e-mail. Recebem uma conta nova (ou têm o acesso liberado, se já tiverem conta) só para avaliar Trabalhos deste evento, sem qualquer acesso ao Concurso.</p>

<?php if (empty($avaliadores)): ?>
    <p>Nenhum avaliador cadastrado ainda.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Nome</th><th>E-mail</th><th>Convidado em</th><th>Ações</th></tr>
        <?php foreach ($avaliadores as $avaliador): ?>
        <tr>
            <td><?php echo htmlspecialchars($avaliador['usuario_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($avaliador['usuario_email'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($avaliador['convidado_em'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <form method="post" action="<?php echo url('trabalhos/avaliadorRemover/' . (int) $avaliador['id']); ?>" onsubmit="return confirm('Remover este avaliador?');"><?= campoCsrf() ?>
                    <button type="submit" class="btn-icone" title="Remover">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                            <path d="M10 11v6"></path>
                            <path d="M14 11v6"></path>
                            <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                        </svg>
                    </button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<h2>Convidar avaliador</h2>
<form method="post" action="<?php echo url('trabalhos/avaliadores/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
    <div class="busca-usuario" data-busca-usuario data-endpoint="<?php echo url('trabalhos/buscarUsuarios'); ?>" data-preencher-nome="[name=nome]" data-preencher-email="[name=email]">
        <label>Buscar usuário já cadastrado (opcional, nome ou e-mail):
            <input type="text" data-busca-usuario-input autocomplete="off" size="40" placeholder="Digite ao menos 2 caracteres">
        </label>
        <ul class="busca-usuario-resultados" hidden data-busca-usuario-resultados></ul>
        <input type="hidden" data-busca-usuario-id>
    </div>
    <p style="color:#555;font-size:0.9em;">Selecionar um resultado preenche nome e e-mail abaixo. Se a pessoa não aparecer na busca, ela ainda não tem conta: digite os dados manualmente e uma conta nova será criada.</p>
    <label>Nome: <input type="text" name="nome" required maxlength="150"></label>
    <label>E-mail: <input type="email" name="email" required maxlength="150"></label>
    <button type="submit">Convidar</button>
</form>
