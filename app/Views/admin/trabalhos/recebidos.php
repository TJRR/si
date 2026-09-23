<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Trabalhos recebidos: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('trabalhos/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<?php if (empty($trabalhos)): ?>
    <p>Nenhum trabalho submetido ainda.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Título</th><th>Autor principal</th><th>Eixo</th><th>Natureza</th><th>Situação</th><th>Avaliadores</th><th>Submetido em</th><th>Ações</th></tr>
        <?php foreach ($trabalhos as $trabalho): ?>
        <tr>
            <td><?php echo htmlspecialchars($trabalho['titulo'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string) $trabalho['autor_principal_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string) $trabalho['eixo_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string) $trabalho['natureza_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($trabalho['situacao_rotulo'], ENT_QUOTES, 'UTF-8'); ?><?php echo (int) $trabalho['selecionado'] === 1 ? ' (selecionado)' : ''; ?></td>
            <td>
                <?php echo (int) $trabalho['total_designacoes']; ?> de <?php echo (int) $quantidadeConfigurada; ?>
                <?php if ($quantidadeConfigurada > 0 && $trabalho['total_designacoes'] < $quantidadeConfigurada): ?>
                    <strong class="texto-alerta">(incompleto)</strong>
                <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($trabalho['submetido_em'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <a href="<?php echo url('trabalhos/recebidoVer/' . (int) $trabalho['id']); ?>" class="btn-icone" title="Ver">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
