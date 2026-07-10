<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Minha equipe</h1>

<p>
    <a href="<?php echo url('participante/meusDados'); ?>">Meus dados</a>
    |
    <a href="<?php echo url('participante/submissoes'); ?>">Submissões</a>
</p>

<p><strong>Equipe:</strong> <?php echo htmlspecialchars($equipe['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?></p>
<p><strong>Trilha:</strong> <?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></p>
<p><strong>Tema/Desafio:</strong> <?php echo $tema !== null ? htmlspecialchars($tema['nome'], ENT_QUOTES, 'UTF-8') : 'ainda não escolhido (será definido na submissão da ideia)'; ?></p>

<h2>Integrantes</h2>
<table border="1" cellpadding="6">
    <tr><th>Nome</th><th>Papel</th><th>Situação da inscrição</th></tr>
    <?php foreach ($colegas as $colega): ?>
    <tr>
        <td>
            <?php echo htmlspecialchars($colega['nome'], ENT_QUOTES, 'UTF-8'); ?>
            <?php echo (int) $colega['id'] === (int) $participanteAtualId ? ' (você)' : ''; ?>
        </td>
        <td><?php echo $colega['papel'] === 'lider' ? 'Líder' : 'Integrante'; ?></td>
        <td>
            <?php
            echo [
                'pendente' => 'Aguardando homologação',
                'homologado' => 'Homologado',
                'rejeitado' => 'Rejeitado',
            ][$colega['status_homologacao']];
            ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
