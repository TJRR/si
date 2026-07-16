<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php if (empty($conteudoSubmissao)): ?>
    <p><em>Esta submissão não tem um formulário associado.</em></p>
<?php else: ?>
    <div class="ficha-submissao">
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
