<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-avaliacao">
    <h1>Lançar notas — Submissão #<?php echo (int) $submissao['id']; ?></h1>

    <p><a href="<?php echo url('avaliacao/submissoes/' . (int) $etapa['id']); ?>">Voltar às submissões</a></p>

    <?php if ($sigiloCego): ?>
        <p><em>Avaliação cega: dados de equipe/participantes ocultos.</em></p>
    <?php endif; ?>

    <?php if (!empty($erro)): ?>
        <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <?php if ($resultadoPublicado): ?>
        <p><strong>O resultado desta etapa já foi publicado — as notas abaixo são apenas para consulta.</strong></p>
    <?php elseif ($avaliacaoTravada): ?>
        <p><strong>Sua avaliação desta submissão já foi concluída — as notas abaixo são apenas para consulta.</strong></p>
    <?php else: ?>
        <p id="progresso-avaliacao"><?php echo (int) $criteriosJaNotados; ?> de <?php echo (int) $totalCriterios; ?> critérios avaliados</p>
    <?php endif; ?>

    <h2>Conteúdo da submissão</h2>
    <?php if (empty($conteudoSubmissao)): ?>
        <p><em>Esta submissão não tem um formulário associado.</em></p>
    <?php else: ?>
        <div class="ficha-submissao ficha-submissao-sticky">
            <?php foreach ($conteudoSubmissao as $item): ?>
                <?php $campo = $item['campo']; $valor = $item['valor']; ?>
                <div class="ficha-item">
                    <div class="ficha-item-label"><?php echo htmlspecialchars($campo['rotulo'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="ficha-item-valor">
                        <?php if ($valor === null || $valor === ''): ?>
                            <em>Não preenchido</em>

                        <?php elseif ($campo['tipo'] === 'link_youtube'): ?>
                            <?php $videoId = \App\Validation\YoutubeValidador::extrairId($valor); ?>
                            <?php if ($videoId !== null): ?>
                                <div style="position:relative; max-width:480px; padding-top:270px;">
                                    <iframe src="https://www.youtube.com/embed/<?php echo htmlspecialchars($videoId, ENT_QUOTES, 'UTF-8'); ?>"
                                            style="position:absolute; top:0; left:0; width:100%; height:100%; border:0;"
                                            allowfullscreen></iframe>
                                </div>
                            <?php else: ?>
                                <a href="<?php echo htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); ?></a>
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
    <?php endif; ?>

    <h2>Notas por critério</h2>
    <form method="post" action="<?php echo url('avaliacao/notar/' . (int) $submissao['id']); ?>" id="form-notas">
        <?php foreach ($criterios as $criterio): ?>
            <section class="criterio-bloco" data-criterio-nome="<?php echo htmlspecialchars($criterio['nome'], ENT_QUOTES, 'UTF-8'); ?>">
                <h3><?php echo htmlspecialchars($criterio['nome'], ENT_QUOTES, 'UTF-8'); ?></h3>
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
                    <span class="campo-nota-escala">(escala: <?php echo number_format((float) $criterio['escala_min'], 1, ',', '.'); ?> a <?php echo number_format((float) $criterio['escala_max'], 1, ',', '.'); ?>)</span>
                </label>

                <?php if ($etapa['modo_feedback_avaliador'] === 'criterio'): ?>
                    <label>Feedback sobre "<?php echo htmlspecialchars($criterio['nome'], ENT_QUOTES, 'UTF-8'); ?>":<br>
                        <textarea name="feedback[<?php echo (int) $criterio['id']; ?>]" rows="4" <?php echo $avaliacaoTravada ? 'readonly' : ''; ?>><?php echo isset($notasAtuais[$criterio['id']]['feedback']) ? htmlspecialchars((string) $notasAtuais[$criterio['id']]['feedback'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                    </label>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>

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
