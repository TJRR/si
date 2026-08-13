<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Bem-vindo, <?php echo htmlspecialchars(\App\Core\Auth::nome(), ENT_QUOTES, 'UTF-8'); ?></h1>

<div class="admin-dashboard-cards">
    <div class="admin-stat-card">
        <span class="admin-stat-numero"><?php echo (int) $totalParticipantes; ?></span>
        <span class="admin-stat-rotulo">Participantes</span>
    </div>
    <div class="admin-stat-card">
        <span class="admin-stat-numero"><?php echo (int) $totalEquipes; ?></span>
        <span class="admin-stat-rotulo">Equipes</span>
    </div>
    <div class="admin-stat-card">
        <span class="admin-stat-numero"><?php echo (int) $totalAvaliadores; ?></span>
        <span class="admin-stat-rotulo">Avaliadores</span>
    </div>
    <div class="admin-stat-card">
        <span class="admin-stat-numero"><?php echo (int) $totalConcursosAtivos; ?></span>
        <span class="admin-stat-rotulo">Concursos ativos</span>
    </div>
    <div class="admin-stat-card">
        <span class="admin-stat-numero"><?php echo (int) $totalConcursosRealizados; ?></span>
        <span class="admin-stat-rotulo">Concursos realizados</span>
    </div>
    <div class="admin-stat-card">
        <span class="admin-stat-numero"><?php echo (int) $totalCadastrosPendentes; ?></span>
        <span class="admin-stat-rotulo">Cadastros pendentes</span>
    </div>
</div>

<?php if (!empty($etapasAvaliacaoVigentes)): ?>
    <h2>Progresso da avaliação</h2>
    <?php foreach ($etapasAvaliacaoVigentes as $item): ?>
        <a class="admin-progresso-item admin-progresso-link" href="<?php echo url('designacoes/progresso/' . (int) $item['etapa_id']); ?>" title="Ver progresso por avaliador">
            <div class="admin-progresso-cabecalho">
                <span><?php echo htmlspecialchars($item['trilha_nome'] . ' — ' . $item['etapa_nome'], ENT_QUOTES, 'UTF-8'); ?></span>
                <span><?php echo (int) $item['percentual']; ?>% (<?php echo (int) $item['completas']; ?> de <?php echo (int) $item['total']; ?>)</span>
            </div>
            <div class="admin-progresso-barra-fundo">
                <div class="admin-progresso-barra-preenchimento" style="width: <?php echo (int) $item['percentual']; ?>%;"></div>
            </div>
        </a>
    <?php endforeach; ?>
<?php endif; ?>

<?php
    // Fase 29 (Tira-Duvidas): icones de acao compartilhados pelas duas
    // tabelas abaixo (todas as duvidas / escaladas pra mim) - Retomar so'
    // faz sentido na visao geral do Administrador (puxar de volta uma
    // escalada que esta' com OUTRA pessoa), nao na lista pessoal.
    $rotulosStatusDuvida = ['recebida' => 'Recebida', 'escalada' => 'Em análise', 'respondida' => 'Respondida'];
    $coresStatusDuvida = ['recebida' => 'azul', 'escalada' => 'laranja', 'respondida' => 'verde'];

    $renderizarAcoesDuvida = function ($duvida, $mostrarRetomar) {
        $urlVer = url('duvidaAdmin/ver/' . (int) $duvida['id']);
        ?>
        <div class="acoes-icones">
            <a href="<?php echo htmlspecialchars($urlVer, ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Ver">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            </a>
            <?php if ($duvida['status'] !== 'respondida'): ?>
                <a href="<?php echo htmlspecialchars($urlVer . '#responder', ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Responder">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="9 17 4 12 9 7"></polyline>
                        <path d="M20 18v-2a4 4 0 0 0-4-4H4"></path>
                    </svg>
                </a>
                <a href="<?php echo htmlspecialchars($urlVer . '#escalar', ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Escalar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="15 17 20 12 15 7"></polyline>
                        <path d="M4 18v-2a4 4 0 0 1 4-4h12"></path>
                    </svg>
                </a>
            <?php endif; ?>
            <?php if ($mostrarRetomar && $duvida['status'] === 'escalada'): ?>
                <form method="post" action="<?php echo url('duvidaAdmin/retomar/' . (int) $duvida['id']); ?>" onsubmit="return confirm('Retomar esta dúvida pra fila geral?');">
                    <button type="submit" class="btn-icone" title="Retomar pra fila geral">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                            <path d="M3 3v5h5"></path>
                        </svg>
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <?php
    };
?>

<?php if (!empty($souAdministradorDuvidas)): ?>
    <h2 style="margin-top:1.5rem;">Dúvidas</h2>
    <div class="admin-dashboard-cards">
        <div class="admin-stat-card">
            <span class="admin-stat-numero"><?php echo (int) $contagemDuvidas['recebida']; ?></span>
            <span class="admin-stat-rotulo">Recebidas</span>
        </div>
        <div class="admin-stat-card">
            <span class="admin-stat-numero"><?php echo (int) $contagemDuvidas['escalada']; ?></span>
            <span class="admin-stat-rotulo">Escaladas</span>
        </div>
        <div class="admin-stat-card">
            <span class="admin-stat-numero"><?php echo (int) $contagemDuvidas['respondida']; ?></span>
            <span class="admin-stat-rotulo">Respondidas</span>
        </div>
    </div>

    <form method="get" action="<?php echo config('base_path'); ?>/index.php">
        <input type="hidden" name="r" value="home/administrativo">
        <label>Estado:
            <select name="duvidas_status" onchange="this.form.submit()">
                <option value="" <?php echo empty($duvidasStatusFiltro) ? 'selected' : ''; ?>>Recebidas e escaladas</option>
                <option value="recebida" <?php echo $duvidasStatusFiltro === 'recebida' ? 'selected' : ''; ?>>Recebidas</option>
                <option value="escalada" <?php echo $duvidasStatusFiltro === 'escalada' ? 'selected' : ''; ?>>Escaladas</option>
                <option value="respondida" <?php echo $duvidasStatusFiltro === 'respondida' ? 'selected' : ''; ?>>Respondidas</option>
                <option value="todas" <?php echo $duvidasStatusFiltro === 'todas' ? 'selected' : ''; ?>>Todas</option>
            </select>
        </label>
    </form>

    <?php if (empty($todasDuvidas)): ?>
        <p>Nenhuma dúvida neste filtro.</p>
    <?php else: ?>
        <table border="1" cellpadding="6">
            <tr><th>Pergunta</th><th>Equipe</th><th>Status</th><th>Responsável</th><th>Registrada em</th><th>SLA</th><th>Ações</th></tr>
            <?php foreach ($todasDuvidas as $duvida): ?>
            <tr>
                <td><?php echo htmlspecialchars(mb_substr($duvida['pergunta'], 0, 60) . (mb_strlen($duvida['pergunta']) > 60 ? '…' : ''), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($duvida['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><span class="status-pill <?php echo $coresStatusDuvida[$duvida['status']]; ?>"><?php echo $rotulosStatusDuvida[$duvida['status']]; ?></span></td>
                <td><?php echo $duvida['responsavel_nome'] !== null ? htmlspecialchars($duvida['responsavel_nome'], ENT_QUOTES, 'UTF-8') : '—'; ?></td>
                <td><?php echo htmlspecialchars(formatarDataHora($duvida['criado_em']), ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <?php if (!empty($duvida['atrasada'])): ?>
                        <span class="status-pill vermelho">Atrasada</span>
                    <?php else: ?>
                        <span class="status-pill verde">Em dia</span>
                    <?php endif; ?>
                </td>
                <td><?php $renderizarAcoesDuvida($duvida, true); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
<?php endif; ?>

<h2 style="margin-top:1.5rem;">Escaladas para mim</h2>
<?php if (empty($minhasEscaladasDuvidas)): ?>
    <p>Nenhuma dúvida escalada pra você no momento.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Pergunta</th><th>Equipe</th><th>Trilha</th><th>Registrada em</th><th>SLA</th><th>Ações</th></tr>
        <?php foreach ($minhasEscaladasDuvidas as $duvida): ?>
        <tr>
            <td><?php echo htmlspecialchars(mb_substr($duvida['pergunta'], 0, 60) . (mb_strlen($duvida['pergunta']) > 60 ? '…' : ''), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($duvida['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($duvida['trilha_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars(formatarDataHora($duvida['criado_em']), ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <?php if (!empty($duvida['atrasada'])): ?>
                    <span class="status-pill vermelho">Atrasada</span>
                <?php else: ?>
                    <span class="status-pill verde">Em dia</span>
                <?php endif; ?>
            </td>
            <td><?php $renderizarAcoesDuvida($duvida, false); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
