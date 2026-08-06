<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-avaliacao pagina-avaliacao-larga">
    <?php include __DIR__ . '/_cabecalho_notar.php'; ?>

    <?php $conteudoCompartilhado = !empty($criterios) && isset($conteudoPorCriterio[$criterios[0]['id']]) ? $conteudoPorCriterio[$criterios[0]['id']] : []; ?>
    <?php if (empty($conteudoCompartilhado)): ?>
        <p><em>Esta submissão não tem conteúdo associado aos critérios.</em></p>
    <?php else: ?>
        <details class="ficha-submissao-colapsavel">
            <summary>Conteúdo da submissão</summary>
        <div class="ficha-submissao">
            <?php foreach ($conteudoCompartilhado as $item): ?>
                <?php $campo = $item['campo']; $valor = $item['valor']; ?>
                <div class="ficha-item">
                    <div class="ficha-item-label"><?php echo htmlspecialchars($campo['rotulo'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="ficha-item-valor">
                        <?php if ($valor === null || $valor === ''): ?>
                            <em>Não preenchido</em>

                        <?php elseif ($campo['tipo'] === 'link_youtube'): ?>
                            <?php $videoId = \App\Validation\YoutubeValidador::extrairId($valor); ?>
                            <?php if ($videoId !== null): ?>
                                <div style="position:relative; width:100%; padding-top:56.25%;">
                                    <iframe src="https://www.youtube.com/embed/<?php echo htmlspecialchars($videoId, ENT_QUOTES, 'UTF-8'); ?>"
                                            style="position:absolute; top:0; left:0; width:100%; height:100%; border:0;"
                                            allowfullscreen></iframe>
                                </div>
                            <?php elseif (\App\Validation\YoutubeValidador::valido($valor)): ?>
                                <a href="<?php echo htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); ?></a>
                            <?php else: ?>
                                <?php echo htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); ?>
                            <?php endif; ?>

                        <?php elseif ($campo['tipo'] === 'link_externo'): ?>
                            <?php if (linkHttpValido($valor)): ?>
                                <a href="<?php echo htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); ?></a>
                            <?php else: ?>
                                <?php echo htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); ?>
                            <?php endif; ?>

                        <?php elseif ($campo['tipo'] === 'upload_pdf'): ?>
                            <a href="<?php echo url('avaliacao/baixarArquivo/' . (int) $submissao['id'] . '/' . (int) $campo['id']); ?>" target="_blank">
                                <?php echo htmlspecialchars($valor['nome_original'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>

                        <?php elseif ($campo['tipo'] === 'grupo_participantes'): ?>
                            <table border="1" cellpadding="4">
                                <tr><th>Nome</th><th>CPF</th><th>E-mail</th><th>Telefone</th><th>Vínculo/Profissão</th></tr>
                                <?php foreach ($valor as $participante): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($participante['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($participante['cpf'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($participante['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($participante['telefone'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($participante['vinculo_profissao'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>

                        <?php else: ?>
                            <?php echo nl2br(htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8')); ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        </details>
    <?php endif; ?>

    <?php $colunasCriterios = min(5, count($criterios)); ?>
    <form method="post" action="<?php echo url('avaliacao/notar/' . (int) $submissao['id']); ?>" id="form-notas">
        <div class="criterios-compartilhados-grade" style="grid-template-columns: repeat(<?php echo $colunasCriterios; ?>, minmax(0, 1fr));">
            <?php foreach ($criterios as $criterio): ?>
                <section class="criterio-bloco criterio-card" data-criterio-nome="<?php echo htmlspecialchars($criterio['nome'], ENT_QUOTES, 'UTF-8'); ?>">
                    <h3><?php echo htmlspecialchars($criterio['nome'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <span class="campo-nota-escala">peso <?php echo number_format((float) $criterio['peso'], 2, ',', '.'); ?></span>

                    <?php if (!empty($criterio['descricao'])): ?>
                        <p><?php echo nl2br(htmlspecialchars($criterio['descricao'], ENT_QUOTES, 'UTF-8')); ?></p>
                    <?php endif; ?>

                    <label>
                        Nota
                        <input type="number"
                               class="campo-nota"
                               name="nota[<?php echo (int) $criterio['id']; ?>]"
                               min="<?php echo htmlspecialchars((string) $criterio['escala_min'], ENT_QUOTES, 'UTF-8'); ?>"
                               max="<?php echo htmlspecialchars((string) $criterio['escala_max'], ENT_QUOTES, 'UTF-8'); ?>"
                               step="0.1"
                               placeholder="<?php echo number_format((float) $criterio['escala_min'], 1, ',', '.'); ?> – <?php echo number_format((float) $criterio['escala_max'], 1, ',', '.'); ?>"
                               value="<?php echo isset($notasAtuais[$criterio['id']]) ? htmlspecialchars((string) $notasAtuais[$criterio['id']]['nota'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                               <?php echo $avaliacaoTravada ? 'readonly' : ''; ?>>
                        <span class="campo-nota-escala">escala: <?php echo number_format((float) $criterio['escala_min'], 1, ',', '.'); ?> a <?php echo number_format((float) $criterio['escala_max'], 1, ',', '.'); ?></span>
                    </label>

                    <?php if ($etapa['modo_feedback_avaliador'] === 'criterio'): ?>
                        <?php $feedbackAtual = isset($notasAtuais[$criterio['id']]['feedback']) ? (string) $notasAtuais[$criterio['id']]['feedback'] : ''; ?>
                        <button type="button" data-toggle-feedback><?php echo $feedbackAtual !== '' ? 'Ocultar feedback' : 'Adicionar feedback'; ?></button>
                        <label>Feedback sobre "<?php echo htmlspecialchars($criterio['nome'], ENT_QUOTES, 'UTF-8'); ?>":<br>
                            <textarea name="feedback[<?php echo (int) $criterio['id']; ?>]" rows="4" <?php echo $feedbackAtual === '' ? 'hidden' : ''; ?> <?php echo $avaliacaoTravada ? 'readonly' : ''; ?>><?php echo htmlspecialchars($feedbackAtual, ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </label>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        </div>

        <?php if ($etapa['modo_feedback_avaliador'] === 'submissao'): ?>
            <section class="criterio-bloco">
                <h3>Feedback desta submissão</h3>
                <label>
                    <textarea name="feedback_submissao" id="campo-feedback-submissao" rows="6" <?php echo $avaliacaoTravada ? 'readonly' : ''; ?>><?php echo $feedbackSubmissaoAtual !== null ? htmlspecialchars($feedbackSubmissaoAtual['feedback'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                </label>
            </section>
        <?php endif; ?>

        <?php if (!$avaliacaoTravada): ?>
            <button type="submit" id="btn-salvar-notas" class="btn btn-bordered">Salvar notas</button>
        <?php endif; ?>
    </form>
</div>
<script src="<?php echo config('base_path'); ?>/assets/js/avaliacao-notar.js"></script>
