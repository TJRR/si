<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Textos dos avisos do Evento</h1>

<p style="color:#555;font-size:0.9em;">Texto dos avisos que o sistema manda por correio eletrônico nos eventos. Enquanto um aviso não tiver texto próprio, ele sai com o texto padrão do sistema. A assinatura com os dados de Contato é acrescentada sozinha ao fim de todos. Os avisos do Concurso não aparecem aqui.</p>

<div class="tabela-scroll">
    <table border="1" cellpadding="6">
        <tr>
            <th>Aviso</th>
            <th>Quando é enviado</th>
            <th>Texto</th>
            <th>Ações</th>
        </tr>
        <?php foreach ($avisos as $chave => $aviso): ?>
            <?php $gravado = isset($gravados[$chave]) ? $gravados[$chave] : null; ?>
            <tr>
                <td><?php echo htmlspecialchars($aviso['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($aviso['quando'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <?php if ($gravado !== null): ?>
                        <span class="status-pill verde">Texto próprio</span>
                        <br><small>Alterado em <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($gravado['atualizado_em'])), ENT_QUOTES, 'UTF-8'); ?></small>
                    <?php else: ?>
                        <span class="status-pill">Texto padrão</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="<?php echo url('textosAvisoEvento/editar/' . $chave); ?>" class="btn-icone" title="Editar o texto">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
