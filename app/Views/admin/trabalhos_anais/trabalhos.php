<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
$esc = function ($texto) {
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
};

$totalIncluidos = 0;
foreach ($linhas as $linha) {
    if ($linha['incluido']) {
        $totalIncluidos++;
    }
}
?>
<div class="pagina-titulo-acoes">
    <h1>Trabalhos nos Anais: <?php echo $esc($evento['nome']); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('trabalhoAnais/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<p style="color:#555;font-size:0.9em;">Lista dos trabalhos aprovados. Todos vêm marcados como constando nos Anais; desmarque quem não deve constar (por exemplo, trabalho não apresentado no evento). Esta lista define quais trabalhos entram no volume montado pelo sistema (aba Montagem dos Anais), orienta quem monta o PDF fora do sistema e decide quem recebe o aviso de publicação e a indicação "Publicado nos Anais" no aplicativo.</p>

<?php if (!$resultadoPublicado): ?>
    <p><span class="selo-situacao laranja">Resultado não publicado</span> Os trabalhos só aparecem aqui depois que o resultado de Trabalhos é publicado (aba Resultado).</p>
<?php elseif (empty($linhas)): ?>
    <p>Nenhum trabalho aprovado neste evento.</p>
<?php else: ?>
    <?php if ($estaPublicado): ?>
        <p><span class="selo-situacao verde">Anais publicados</span> A lista está bloqueada para os autores não verem a indicação mudar por baixo deles. Para alterá-la, despublique os Anais na aba Anais.</p>
    <?php endif; ?>

    <p><strong><?php echo (int) $totalIncluidos; ?></strong> de <?php echo count($linhas); ?> trabalho(s) constam nos Anais.</p>

    <?php if (!$estaPublicado): ?>
        <p><a href="<?php echo url('trabalhoAnais/trabalhos/' . (int) $evento['id']); ?>&amp;sugerir=1" class="btn">Sugerir exclusão dos não apresentados</a></p>
    <?php endif; ?>

    <?php if ($sugestao === 'aplicada'): ?>
        <p><span class="selo-situacao laranja">Sugestão aplicada, nada foi gravado</span> Os trabalhos selecionados para apresentação que não têm a marca de apresentado (Certificados, Apresentações) aparecem desmarcados e com o motivo preenchido. Confira a lista e use "Salvar lista" para gravar, ou volte sem salvar para descartar a sugestão.</p>
    <?php elseif ($sugestao === 'sem_marcas'): ?>
        <p><span class="selo-situacao laranja">Sugestão não aplicada</span> Nenhum trabalho deste evento tem a marca de apresentado (Certificados, Apresentações). Sem nenhuma marca, a sugestão desmarcaria todos os trabalhos selecionados, então nada foi alterado.</p>
    <?php endif; ?>

    <form method="post" action="<?php echo url('trabalhoAnais/trabalhos/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
        <table border="1" cellpadding="6">
            <tr>
                <th>Consta</th><th>Posição</th><th>Protocolo</th><th>Título</th><th>Autoria</th><th>Eixo</th><th>Nota</th><th>Apresentado</th><th>Declarações</th><th>Motivo, se não constar</th>
            </tr>
            <?php foreach ($linhas as $linha): ?>
            <tr>
                <td>
                    <input type="checkbox" name="incluir[]" value="<?php echo (int) $linha['id']; ?>" <?php echo $linha['incluido'] ? 'checked' : ''; ?> <?php echo $estaPublicado ? 'disabled' : ''; ?> aria-label="Consta nos Anais: <?php echo $esc($linha['titulo']); ?>">
                </td>
                <td><?php echo $linha['posicao'] !== null ? (int) $linha['posicao'] . 'º' : '&mdash;'; ?></td>
                <td><?php echo (int) $linha['id']; ?></td>
                <td><?php echo $esc($linha['titulo']); ?></td>
                <td>
                    <?php foreach ($linha['autores'] as $autor): ?>
                        <?php echo $esc($autor); ?><br>
                    <?php endforeach; ?>
                </td>
                <td><?php echo $esc($linha['eixo_nome']); ?></td>
                <td><?php echo $linha['nota_final'] !== null ? $esc(number_format((float) $linha['nota_final'], $casasDecimais, ',', '.')) : '&mdash;'; ?></td>
                <td>
                    <?php if (!$linha['selecionado']): ?>
                        <span title="Trabalho não selecionado para apresentação">&mdash;</span>
                    <?php elseif ($linha['apresentado']): ?>
                        Sim
                    <?php else: ?>
                        <span class="status-pill laranja">Não</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (empty($linha['declaracoes_pendentes'])): ?>
                        Aceitas
                    <?php else: ?>
                        <span class="status-pill laranja" title="<?php echo $esc(implode('; ', $linha['declaracoes_pendentes'])); ?>">Pendente</span>
                        <br><small><?php echo $esc(implode('; ', $linha['declaracoes_pendentes'])); ?></small>
                    <?php endif; ?>
                </td>
                <td>
                    <input type="text" name="motivo[<?php echo (int) $linha['id']; ?>]" maxlength="<?php echo (int) \App\Services\EventoAnaisService::MOTIVO_MAXIMO; ?>" value="<?php echo $esc($linha['motivo']); ?>" <?php echo $estaPublicado ? 'disabled' : ''; ?> aria-label="Motivo de não constar: <?php echo $esc($linha['titulo']); ?>">
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <p style="color:#555;font-size:0.9em;">"Declarações: Pendente" é só um alerta: falta o aceite de alguma declaração obrigatória cadastrada em Trabalhos, Declarações. Não impede o trabalho de constar, mas confira a autorização de publicação antes de publicar os Anais. O CPF e o e-mail dos autores nunca aparecem aqui.</p>

        <?php if (!$estaPublicado): ?>
            <div class="form-acoes">
                <button type="submit">Salvar lista</button>
            </div>
        <?php endif; ?>
    </form>
<?php endif; ?>
