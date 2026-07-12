<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
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
<?php endif; ?>

<h2>Conteúdo da submissão</h2>
<?php if (empty($conteudoSubmissao)): ?>
    <p><em>Esta submissão não tem um formulário associado.</em></p>
<?php else: ?>
    <table border="1" cellpadding="6" style="margin-bottom:1.5em;">
        <?php foreach ($conteudoSubmissao as $item): ?>
            <?php $campo = $item['campo']; $valor = $item['valor']; ?>
            <tr>
                <th style="text-align:left; vertical-align:top; white-space:nowrap;">
                    <?php echo htmlspecialchars($campo['rotulo'], ENT_QUOTES, 'UTF-8'); ?>
                </th>
                <td>
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
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<h2>Notas por critério</h2>
<form method="post" action="<?php echo url('avaliacao/notar/' . (int) $submissao['id']); ?>">
    <table border="1" cellpadding="6">
        <tr><th>Critério</th><th>Escala</th><th>Nota</th></tr>
        <?php foreach ($criterios as $criterio): ?>
        <tr>
            <td><?php echo htmlspecialchars($criterio['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo number_format((float) $criterio['escala_min'], 1, ',', '.'); ?> a <?php echo number_format((float) $criterio['escala_max'], 1, ',', '.'); ?></td>
            <td>
                <input type="text"
                       name="nota[<?php echo (int) $criterio['id']; ?>]"
                       value="<?php echo isset($notasAtuais[$criterio['id']]) ? htmlspecialchars((string) $notasAtuais[$criterio['id']]['nota'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                       <?php echo $resultadoPublicado ? 'readonly' : ''; ?>>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php if (!$resultadoPublicado): ?>
        <button type="submit">Salvar notas</button>
    <?php endif; ?>
</form>
