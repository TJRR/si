<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
// buscarPorId() e' SELECT * (sem join com equipes), entao o nome da equipe vem
// dos proprios convidados - que ja foram resolvidos a partir do equipe_id do
// horario. Vazio significa horario nunca reservado.
$nomeEquipe = !empty($convidados) ? $convidados[0]['nome_equipe'] : null;
?>
<p>
    <strong>Mentoria
        <?php if ($nomeEquipe !== null): ?>
            com a equipe <?php echo htmlspecialchars($nomeEquipe, ENT_QUOTES, 'UTF-8'); ?>
        <?php else: ?>
            (horário não reservado por nenhuma equipe)
        <?php endif; ?>
    </strong>
    — <?php echo htmlspecialchars(formatarDataHora($horario['data_inicio']), ENT_QUOTES, 'UTF-8'); ?>
    às <?php echo htmlspecialchars(formatarDataHora($horario['data_fim']), ENT_QUOTES, 'UTF-8'); ?>
    <?php echo sufixoFusoHorario(); ?>
</p>

<?php include __DIR__ . '/../_presenca_relatorio.php'; ?>
