<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Banco de perguntas frequentes</h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('faq/novo'); ?>" class="btn-acao">+ Nova pergunta</a>
    </div>
</div>
<p>Banco global, acumulativo entre edições. Para exibir uma pergunta na home de um concurso específico, ative-a na tela "FAQ desta edição" (dentro do concurso, na árvore).</p>

<?php if (empty($faqs)): ?>
    <p>Nenhuma pergunta cadastrada ainda.</p>
<?php else: ?>
    <div class="tabela-scroll">
        <table>
            <tr><th>Categoria</th><th>Pergunta</th><th>Ações</th></tr>
            <?php foreach ($faqs as $faq): ?>
            <tr>
                <td><?php echo htmlspecialchars((string) $faq['categoria'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($faq['pergunta'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <div class="acoes-icones">
                        <a href="<?php echo url('faq/editar/' . (int) $faq['id']); ?>" class="btn-icone" title="Editar">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </a>
                        <form method="post" action="<?php echo url('faq/remover'); ?>" onsubmit="return confirm('Remover esta pergunta do banco global? Só funciona se ela não estiver ativa em nenhuma edição.');">
                            <input type="hidden" name="id" value="<?php echo (int) $faq['id']; ?>">
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
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>
