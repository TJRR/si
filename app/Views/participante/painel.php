<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Minha inscrição</h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('mentoria/index'); ?>" class="btn-acao">Agendar Mentoria</a>
    </div>
</div>

<div class="admin-card">
    <p>
        <strong><?php echo htmlspecialchars($equipe['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?></strong>
        <span class="status-pill <?php echo $homologado ? 'verde' : 'laranja'; ?>">
            <?php echo $homologado ? 'Equipe homologada' : 'Aguardando homologação'; ?>
        </span>
    </p>
    <p><strong>Trilha:</strong> <?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></p>
    <p><strong>Tema:</strong> <?php echo $tema !== null ? htmlspecialchars($tema['nome'], ENT_QUOTES, 'UTF-8') : 'ainda não escolhido (será definido na submissão da ideia)'; ?></p>
    <?php if ($desafio !== null): ?>
    <p><strong>Desafio:</strong> <?php echo htmlspecialchars($desafio['pergunta'], ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <?php if ($ehLider): ?>
        <p><a href="<?php echo url('participante/editarEquipe'); ?>">Editar equipe</a></p>
    <?php endif; ?>
</div>

<h2>Integrantes</h2>
<table>
    <tr><th>Integrante</th><th>Papel</th><th>Situação</th><th>Ações</th></tr>
    <?php
    $rotulosStatus = ['pendente' => 'Aguardando homologação', 'homologado' => 'Homologado', 'rejeitado' => 'Rejeitado'];
    $coresStatus = ['pendente' => 'laranja', 'homologado' => 'verde', 'rejeitado' => 'vermelho'];
    ?>
    <?php foreach ($colegas as $colega): ?>
        <?php $ehEuMesmo = (int) $colega['id'] === (int) $participanteAtualId; ?>
        <tr>
            <td>
                <?php echo htmlspecialchars($colega['nome'], ENT_QUOTES, 'UTF-8'); ?>
                <?php echo $ehEuMesmo ? ' (você)' : ''; ?>
            </td>
            <td><?php echo $colega['papel'] === 'lider' ? 'Líder' : 'Integrante'; ?></td>
            <td>
                <span class="status-pill <?php echo $coresStatus[$colega['status_homologacao']]; ?>">
                    <?php echo $rotulosStatus[$colega['status_homologacao']]; ?>
                </span>
                <?php if ($colega['status_homologacao'] === 'rejeitado' && !empty($colega['motivo_rejeicao'])): ?>
                    <br><small><?php echo htmlspecialchars($colega['motivo_rejeicao'], ENT_QUOTES, 'UTF-8'); ?></small>
                <?php endif; ?>
            </td>
            <td>
                <div class="acoes-icones">
                    <?php if ($ehEuMesmo): ?>
                        <a href="<?php echo url('participante/meusDados'); ?>" class="btn-icone" title="Editar meus dados">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </a>
                    <?php endif; ?>
                    <?php if ($ehLider && !$ehEuMesmo && $colega['status_homologacao'] === 'homologado'): ?>
                        <?php $confirmacao = htmlspecialchars(addslashes('Promover ' . $colega['nome'] . ' a líder da equipe? Você deixará de ser o líder.'), ENT_QUOTES, 'UTF-8'); ?>
                        <form method="post" action="<?php echo url('participante/promoverLider/' . (int) $colega['id']); ?>" onsubmit="return confirm('<?php echo $confirmacao; ?>');">
                            <button type="submit" class="btn-icone" title="Promover a líder">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="16 12 12 8 8 12"></polyline>
                                    <line x1="12" y1="16" x2="12" y2="8"></line>
                                </svg>
                            </button>
                        </form>
                    <?php endif; ?>
                    <?php if ($ehLider && !$ehEuMesmo): ?>
                        <?php $confirmacaoExcluir = htmlspecialchars(addslashes('Excluir ' . $colega['nome'] . ' da equipe? Esta ação não pode ser desfeita.'), ENT_QUOTES, 'UTF-8'); ?>
                        <form method="post" action="<?php echo url('participante/excluirIntegrante/' . (int) $colega['id']); ?>" onsubmit="return confirm('<?php echo $confirmacaoExcluir; ?>');">
                            <button type="submit" class="btn-icone" title="Excluir integrante">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                                    <path d="M10 11v6"></path>
                                    <path d="M14 11v6"></path>
                                    <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                                </svg>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<h2>Etapas da submissão</h2>
<?php if (!$homologado): ?>
    <p>Sua inscrição ainda não foi homologada — assim que for, as etapas de submissão aparecerão aqui.</p>
<?php elseif (empty($etapas)): ?>
    <p>Nenhuma etapa de submissão disponível no momento para a sua trilha.</p>
<?php else: ?>
    <table>
        <tr><th>Etapa</th><th>Período</th><th>Ação</th></tr>
        <?php foreach ($etapas as $etapa): ?>
        <tr>
            <td><?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <?php if ($etapa['data_inicio'] === null && $etapa['data_fim'] === null): ?>
                    Período não definido
                <?php else: ?>
                    <?php echo htmlspecialchars(formatarData($etapa['data_inicio']), ENT_QUOTES, 'UTF-8'); ?>
                    a
                    <?php echo htmlspecialchars(formatarData($etapa['data_fim']), ENT_QUOTES, 'UTF-8'); ?>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($etapa['motivo_bloqueio'] !== null): ?>
                    <span class="acao-indisponivel" title="<?php echo htmlspecialchars($etapa['motivo_bloqueio'], ENT_QUOTES, 'UTF-8'); ?>">Indisponível</span>
                    <br><small><?php echo htmlspecialchars($etapa['motivo_bloqueio'], ENT_QUOTES, 'UTF-8'); ?></small>
                <?php endif; ?>
                <?php if ($etapa['motivo_bloqueio'] === null || !empty($etapa['feedback_disponivel'])): ?>
                    <div class="acoes-icones">
                        <?php if ($etapa['motivo_bloqueio'] === null): ?>
                            <a href="<?php echo url('submissao/preencher/' . (int) $etapa['id']); ?>" class="btn-icone" title="Preencher">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($etapa['feedback_disponivel'])): ?>
                            <a href="<?php echo url('participante/verFeedback/' . (int) $etapa['submissao_id_feedback']); ?>" class="btn-icone" title="Ver feedback">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
