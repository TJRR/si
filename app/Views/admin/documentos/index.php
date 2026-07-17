<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
$rotulosTipo = [
    'edital' => 'Edital', 'edital_simples' => 'Edital em linguagem simples', 'anexo' => 'Anexo',
    'retificacao' => 'Retificação', 'resultado_final' => 'Resultado final', 'ata' => 'Ata',
];
?>
<div class="pagina-titulo-acoes">
    <h1>Documentos de <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('documentos/novo/' . (int) $concurso['id']); ?>" class="btn-acao">+ Novo documento</a>
    </div>
</div>
<p>Um novo upload com o mesmo tipo e título vira uma nova versão — o arquivo anterior nunca é apagado, só deixa de ser o "atual". Veja o histórico completo pelo ícone de relógio.</p>

<?php if (empty($documentos)): ?>
    <p>Nenhum documento cadastrado ainda.</p>
<?php else: ?>
    <div class="tabela-scroll">
        <table>
            <tr><th>Tipo</th><th>Título</th><th>Versão</th><th>Ações</th></tr>
            <?php foreach ($documentos as $documento): ?>
            <tr>
                <td><?php echo htmlspecialchars(isset($rotulosTipo[$documento['tipo']]) ? $rotulosTipo[$documento['tipo']] : $documento['tipo'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($documento['titulo'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td>v<?php echo (int) $documento['versao']; ?></td>
                <td>
                    <div class="acoes-icones">
                        <a href="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $documento['arquivo_path'], ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Baixar" target="_blank" rel="noopener">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                        </a>
                        <a href="<?php echo url('documentos/historico/' . (int) $concurso['id'] . '/' . urlencode($documento['grupo_documento'])); ?>" class="btn-icone" title="Ver histórico de versões">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </a>
                        <form method="post" action="<?php echo url('documentos/removerGrupo'); ?>" onsubmit="return confirm('Remover TODAS as versões deste documento? Não pode ser desfeito.');">
                            <input type="hidden" name="concurso_id" value="<?php echo (int) $concurso['id']; ?>">
                            <input type="hidden" name="grupo_documento" value="<?php echo htmlspecialchars($documento['grupo_documento'], ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" class="btn-icone" title="Remover todas as versões">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
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
