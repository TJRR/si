<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Eventos avulsos do cronograma de <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('eventosCronograma/novo/' . (int) $concurso['id']); ?>" class="btn-acao">+ Novo evento</a>
    </div>
</div>
<p>Estes eventos aparecem misturados às Etapas na linha do tempo pública, ordenados automaticamente por data (ex.: cerimônia de premiação, live de dúvidas).</p>

<?php if (empty($eventos)): ?>
    <p>Nenhum evento avulso cadastrado ainda.</p>
<?php else: ?>
    <div class="tabela-scroll">
        <table>
            <tr><th>Título</th><th>Início</th><th>Fim</th><th>Etapa vinculada</th><th>Ações</th></tr>
            <?php foreach ($eventos as $evento): ?>
            <tr>
                <td><?php echo htmlspecialchars($evento['titulo'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($evento['data_inicio'])), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo $evento['data_fim'] ? htmlspecialchars(date('d/m/Y H:i', strtotime($evento['data_fim'])), ENT_QUOTES, 'UTF-8') : '—'; ?></td>
                <td><?php echo $evento['etapa_id'] !== null ? 'Sim' : '—'; ?></td>
                <td>
                    <div class="acoes-icones">
                        <a href="<?php echo url('eventosCronograma/editar/' . (int) $evento['id']); ?>" class="btn-icone" title="Editar">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </a>
                        <form method="post" action="<?php echo url('eventosCronograma/remover'); ?>" onsubmit="return confirm('Remover este evento?');">
                            <input type="hidden" name="id" value="<?php echo (int) $evento['id']; ?>">
                            <input type="hidden" name="concurso_id" value="<?php echo (int) $concurso['id']; ?>">
                            <button type="submit" class="btn-icone" title="Remover">
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
