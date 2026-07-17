<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Histórico de versões</h1>
<p><a href="<?php echo url('documentos/index/' . (int) $concurso['id']); ?>" class="btn-voltar">Voltar aos documentos</a></p>

<?php if (empty($versoes)): ?>
    <p>Nenhuma versão encontrada.</p>
<?php else: ?>
    <div class="tabela-scroll">
        <table>
            <tr><th>Versão</th><th>Título</th><th>Enviado em</th><th>Status</th><th>Ações</th></tr>
            <?php foreach ($versoes as $versao): ?>
            <tr>
                <td>v<?php echo (int) $versao['versao']; ?></td>
                <td><?php echo htmlspecialchars($versao['titulo'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars(formatarData($versao['criado_em']), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><span class="status-pill <?php echo $versao['ativo'] ? 'verde' : ''; ?>"><?php echo $versao['ativo'] ? 'Atual' : 'Substituída'; ?></span></td>
                <td>
                    <a href="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $versao['arquivo_path'], ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Baixar esta versão" target="_blank" rel="noopener">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>
