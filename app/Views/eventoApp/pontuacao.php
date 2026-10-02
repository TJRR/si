<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 58: "Minha pontuação" - total, posição e extrato da pessoa, e os
 * primeiros da classificação geral, com ou sem nome conforme a configuração
 * do evento. O nome de quem está abaixo dos primeiros nunca aparece aqui.
 */
$rotulosOrigem = [
    'presenca' => 'Presença em atividades',
    'competicoes' => 'Competições',
    'conexoes' => 'Conexões',
    'estandes' => 'Visitas a estandes',
    'divulgacao' => 'Divulgação',
    'bonus' => 'Bônus e ações',
];
$mostrarNomes = (int) $config['classificacao_mostrar_nomes'] === 1;
?>

<div class="site-page">
    <?php
    $eventoId = $evento['id'];
    $tituloTopo = $evento['nome'];
    $urlVoltar = url('eventoApp/index/' . (int) $eventoId);
    require __DIR__ . '/_app_bar.php';
    ?>

    <div class="site-form-page">
        <?php require __DIR__ . '/_ajuda_card.php'; ?>
        <h2>Minha pontuação</h2>

        <?php if (!empty($gincanaEncerrada)): ?>
            <p class="status-pill laranja">A gincana foi encerrada em <?php echo htmlspecialchars(formatarDataHora($config['encerramento_em']), ENT_QUOTES, 'UTF-8'); ?>. A classificação abaixo é a final.</p>
        <?php endif; ?>

        <?php if ($indisponivel): ?>
            <p class="status-pill vermelho">A classificação está indisponível no momento. Tente de novo em alguns instantes.</p>
        <?php else: ?>
            <div class="admin-card">
                <?php if ($minhaLinha === null): ?>
                    <p><strong>Você ainda não tem pontos.</strong></p>
                    <p><small>Veja em <a href="<?php echo url('eventoApp/regras/' . (int) $evento['id']); ?>">Regras do jogo</a> como pontuar.</small></p>
                <?php else: ?>
                    <p>
                        <strong><?php echo (int) $minhaLinha['total']; ?> <?php echo (int) $minhaLinha['total'] === 1 ? 'ponto' : 'pontos'; ?></strong>
                        <br>
                        Sua posição: <?php echo (int) $minhaLinha['posicao']; ?>º lugar<?php echo $minhaLinha['empatado'] ? ', com empate' : ''; ?>.
                    </p>
                    <ul>
                        <?php foreach ($rotulosOrigem as $origem => $rotulo): ?>
                            <?php if ((int) $minhaLinha['por_origem'][$origem] > 0): ?>
                                <li><?php echo htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8'); ?>: <?php echo (int) $minhaLinha['por_origem'][$origem]; ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <?php if (!empty($primeiros)): ?>
                <h3>Os primeiros da classificação</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Posição</th>
                            <?php if ($mostrarNomes): ?><th>Nome</th><?php endif; ?>
                            <th>Pontos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($primeiros as $linha): ?>
                            <?php $ehVoce = (int) $linha['inscricao_id'] === (int) $inscricao['id']; ?>
                            <tr<?php echo $ehVoce ? ' style="font-weight:bold;"' : ''; ?>>
                                <td><?php echo (int) $linha['posicao']; ?>º<?php echo $linha['empatado'] ? ' (empate)' : ''; ?></td>
                                <?php if ($mostrarNomes): ?>
                                    <td><?php echo $ehVoce ? 'Você' : htmlspecialchars($linha['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <?php endif; ?>
                                <td>
                                    <?php echo (int) $linha['total']; ?>
                                    <?php echo (!$mostrarNomes && $ehVoce) ? ' (você)' : ''; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>

        <h3>Presenças pontuadas</h3>
        <?php if (empty($presencas)): ?>
            <p>Nenhuma presença pontuada até agora.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($presencas as $presenca): ?>
                    <li>
                        <?php echo htmlspecialchars($presenca['atividade_nome'], ENT_QUOTES, 'UTF-8'); ?>:
                        <?php echo (int) $presenca['pontos_presenca']; ?>
                        <?php if ((int) $presenca['pontos_pontualidade'] > 0): ?>
                            + <?php echo (int) $presenca['pontos_pontualidade']; ?> de pontualidade
                        <?php endif; ?>
                        <?php if ($presenca['anulado_em'] !== null): ?>
                            <br><span class="status-pill vermelho">Anulados</span>
                            <small><?php echo htmlspecialchars((string) $presenca['motivo_anulacao'], ENT_QUOTES, 'UTF-8'); ?></small>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <h3>Competições</h3>
        <?php if (empty($participacoes)): ?>
            <p>Nenhuma participação registrada até agora.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($participacoes as $participacao): ?>
                    <li>
                        <?php echo htmlspecialchars($participacao['competicao_nome'], ENT_QUOTES, 'UTF-8'); ?>:
                        <?php echo (int) $participacao['pontos_creditados']; ?> <?php echo (int) $participacao['pontos_creditados'] === 1 ? 'ponto' : 'pontos'; ?>
                        <?php if ($participacao['anulado_em'] !== null): ?>
                            <br><span class="status-pill vermelho">Anulada pela organização</span>
                            <small><?php echo htmlspecialchars((string) $participacao['motivo_anulacao'], ENT_QUOTES, 'UTF-8'); ?></small>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <p>
            <small>
                O detalhe das outras origens fica na tela de cada uma:
                <a href="<?php echo url('eventoApp/conexoes/' . (int) $evento['id']); ?>">conexões</a>,
                <a href="<?php echo url('eventoApp/estandes/' . (int) $evento['id']); ?>">estandes</a>,
                <a href="<?php echo url('eventoApp/divulgacao/' . (int) $evento['id']); ?>">divulgação</a>
                e os bônus no <a href="<?php echo url('eventoApp/index/' . (int) $evento['id']); ?>">painel</a>.
            </small>
        </p>

        <p><a href="<?php echo url('eventoApp/regras/' . (int) $evento['id']); ?>" class="btn">Regras do jogo</a></p>
    </div>
</div>
