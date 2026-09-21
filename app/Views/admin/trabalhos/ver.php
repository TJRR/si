<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1><?php echo htmlspecialchars($trabalho['titulo'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('trabalhos/recebidos/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<p><strong>Situação:</strong> <?php echo htmlspecialchars($trabalho['situacao_rotulo'], ENT_QUOTES, 'UTF-8'); ?><?php echo (int) $trabalho['selecionado'] === 1 ? ' (selecionado)' : ''; ?></p>
<p><strong>Eixo temático:</strong> <?php echo htmlspecialchars((string) $trabalho['eixo_nome'], ENT_QUOTES, 'UTF-8'); ?></p>
<p><strong>Natureza:</strong> <?php echo htmlspecialchars((string) $trabalho['natureza_nome'], ENT_QUOTES, 'UTF-8'); ?></p>
<p><strong>Submetido em:</strong> <?php echo htmlspecialchars($trabalho['submetido_em'], ENT_QUOTES, 'UTF-8'); ?></p>

<h2>Autoria</h2>
<table border="1" cellpadding="6">
    <tr><th>Papel</th><th>Nome</th><th>CPF</th><th>E-mail</th><th>Cargo</th><th>Órgão de origem</th></tr>
    <?php foreach ($autores as $autor): ?>
    <tr>
        <td><?php echo (int) $autor['eh_autor_principal'] === 1 ? 'Autor principal' : 'Coautor'; ?></td>
        <td><?php echo htmlspecialchars($autor['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars(\App\Validation\CpfValidador::formatar($autor['cpf']), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($autor['email'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) $autor['cargo'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) $autor['orgao_origem'], ENT_QUOTES, 'UTF-8'); ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<h2>Conteúdo</h2>
<?php if ($trabalho['metodo_submissao'] === 'formulario'): ?>
    <div><?php echo $trabalho['conteudo_html']; ?></div>
<?php elseif ($trabalho['metodo_submissao'] === 'link_externo'): ?>
    <p>Versão sem identificação: <a href="<?php echo htmlspecialchars($trabalho['link_avaliacao'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">abrir</a></p>
    <?php if (!empty($trabalho['link_publicacao'])): ?>
        <p>Versão completa: <a href="<?php echo htmlspecialchars($trabalho['link_publicacao'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">abrir</a></p>
    <?php endif; ?>
<?php else: ?>
    <p>Versão sem identificação: <a href="<?php echo url('trabalhos/arquivoAvaliacao/' . (int) $trabalho['id']); ?>">baixar</a></p>
    <?php if (!empty($trabalho['arquivo_publicacao_path'])): ?>
        <p>Versão completa: <a href="<?php echo url('trabalhos/arquivoPublicacao/' . (int) $trabalho['id']); ?>">baixar</a></p>
    <?php endif; ?>
<?php endif; ?>

<h2>Avaliadores designados</h2>
<?php if ($quantidadeConfigurada > 0 && count($designacoes) < $quantidadeConfigurada): ?>
    <p class="texto-alerta"><strong>Atenção:</strong> este trabalho tem <?php echo count($designacoes); ?> de <?php echo (int) $quantidadeConfigurada; ?> avaliadores configurados para este evento.</p>
<?php endif; ?>
<?php if (empty($designacoes)): ?>
    <p>Nenhum avaliador designado ainda.</p>
<?php else: ?>
    <ul>
        <?php foreach ($designacoes as $designacao): ?>
            <li>
                <?php echo htmlspecialchars($designacao['usuario_nome'], ENT_QUOTES, 'UTF-8'); ?>
                <form method="post" action="<?php echo url('trabalhos/recebidoDesignacaoRemover/' . (int) $designacao['id']); ?>" style="display:inline;" onsubmit="return confirm('Remover esta designação?');"><?= campoCsrf() ?>
                    <button type="submit">Remover</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="post" action="<?php echo url('trabalhos/recebidoDesignar/' . (int) $trabalho['id']); ?>"><?= campoCsrf() ?>
    <label>Designar avaliador:
        <select name="usuario_id" required>
            <?php foreach ($avaliadoresDisponiveis as $avaliador): ?>
                <option value="<?php echo (int) $avaliador['usuario_id']; ?>"><?php echo htmlspecialchars($avaliador['usuario_nome'], ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit">Designar</button>
</form>

<h2>Desclassificar</h2>
<form method="post" action="<?php echo url('trabalhos/recebidoDesclassificar/' . (int) $trabalho['id']); ?>" onsubmit="return confirm('Desclassificar este trabalho?');"><?= campoCsrf() ?>
    <label>Motivo: <input type="text" name="motivo" required maxlength="255"></label>
    <button type="submit">Desclassificar</button>
</form>
