<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Conferencia de inscricoes importadas</h1>

<p><a href="<?php echo url('home/administrativo'); ?>">Voltar ao painel</a></p>

<?php if (empty($pendencias)): ?>
    <p>Nenhuma pendencia de conferencia no momento.</p>
<?php else: ?>
    <p><?php echo count($pendencias); ?> pendencia(s) aguardando conferencia.</p>
    <table border="1" cellpadding="6">
        <tr>
            <th>Tipo</th>
            <th>Trilha</th>
            <th>Equipe</th>
            <th>Participante</th>
            <th>Origem</th>
            <th>Descricao</th>
            <th>Acoes</th>
        </tr>
        <?php foreach ($pendencias as $pendencia): ?>
        <?php $dados = $pendencia['dados_brutos_json'] !== null ? json_decode($pendencia['dados_brutos_json'], true) : []; ?>
        <tr>
            <td><?php echo htmlspecialchars($pendencia['tipo'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string) $pendencia['trilha_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string) $pendencia['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string) $pendencia['participante_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($pendencia['aba'] . ' / linha ' . $pendencia['linha_planilha'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($pendencia['descricao'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <?php if ($pendencia['tipo'] === 'cpf_invalido'): ?>
                    <form method="post" action="<?php echo url('revisao/corrigirCpf'); ?>" style="display:inline;">
                        <input type="hidden" name="id" value="<?php echo (int) $pendencia['id']; ?>">
                        <input type="text" name="novo_cpf" placeholder="Novo CPF"
                               value="<?php echo htmlspecialchars((string) (isset($dados['sugestao']) ? $dados['sugestao'] : ''), ENT_QUOTES, 'UTF-8'); ?>"
                               size="14">
                        <button type="submit">Corrigir CPF</button>
                    </form>
                <?php elseif ($pendencia['tipo'] === 'cpf_duplicado_na_equipe'): ?>
                    <form method="post" action="<?php echo url('revisao/removerIntegrante'); ?>" style="display:inline;"
                          onsubmit="return confirm('Remover este integrante duplicado da equipe?');">
                        <input type="hidden" name="id" value="<?php echo (int) $pendencia['id']; ?>">
                        <button type="submit">Remover da equipe</button>
                    </form>
                <?php endif; ?>
                <form method="post" action="<?php echo url('revisao/ignorar'); ?>" style="display:inline;">
                    <input type="hidden" name="id" value="<?php echo (int) $pendencia['id']; ?>">
                    <input type="text" name="observacao" placeholder="Observacao (opcional)" size="16">
                    <button type="submit">Ignorar</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
