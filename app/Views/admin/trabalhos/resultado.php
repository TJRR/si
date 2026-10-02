<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Resultado de Trabalhos: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('trabalhos/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<?php $estaPublicado = $publicadoEm !== null; ?>

<p>
    <?php if ($estaPublicado): ?>
        <span class="selo-situacao verde">Publicado</span>
        Resultado publicado em <?php echo htmlspecialchars(formatarDataHora($publicadoEm), ENT_QUOTES, 'UTF-8'); ?><?php echo $publicadoPor !== null ? ' por ' . htmlspecialchars($publicadoPor, ENT_QUOTES, 'UTF-8') : ''; ?>. Os autores já veem a situação e a pontuação gravadas nessa publicação.
    <?php else: ?>
        <span class="selo-situacao laranja">Não publicado</span>
        Os autores ainda não veem situação nem pontuação: até a publicação, cada trabalho aparece para eles como "Submetido" (a desclassificação aparece na hora).
    <?php endif; ?>
</p>

<p style="color:#555;font-size:0.9em;">A tabela abaixo é a prévia calculada agora, a partir das notas já lançadas. Ao publicar, o sistema grava esse cálculo em cada trabalho, e é isso que os autores passam a ver.<?php echo $estaPublicado ? ' Se uma nota mudar depois da publicação, a tabela muda, mas o que os autores veem só muda se você reabrir o resultado e publicar de novo.' : ''; ?></p>

<?php if (empty($ranking)): ?>
    <p>Nenhum trabalho para exibir ainda.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Posição</th><th>Título</th><th>Autor principal</th><th>Nota</th><th>Desempate</th><th>Aprovação</th><th>Seleção</th></tr>
        <?php foreach ($ranking as $posicao => $linha): ?>
        <tr>
            <td><?php echo $posicao + 1; ?></td>
            <td><?php echo htmlspecialchars($linha['titulo'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string) $linha['autor_principal_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo $linha['nota'] !== null ? htmlspecialchars(number_format($linha['nota'], $casasDecimais, ',', '.'), ENT_QUOTES, 'UTF-8') : 'sem nota lançada'; ?></td>
            <?php
            // Fase 51: item 7.6 do edital - só as linhas que empataram na
            // nota final com o trabalho de cima mostram a regra que decidiu.
            $criterioDesempate = isset($linha['desempate_criterio']) ? (string) $linha['desempate_criterio'] : '';
            ?>
            <td><?php echo $criterioDesempate !== '' ? htmlspecialchars($criterioDesempate, ENT_QUOTES, 'UTF-8') : '&mdash;'; ?></td>
            <td><?php echo $linha['aprovado'] ? 'Aprovado' : 'Não aprovado'; ?></td>
            <td><?php echo in_array($linha['trabalho_id'], $selecionados, true) ? 'Selecionado' : '&mdash;'; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php if ($estaPublicado || !empty($ranking)): ?>
    <?php
    $textoPublicar = 'Publicar o resultado agora? ' . count($ranking) . ' trabalho(s) entram na classificação';
    if ($semNota > 0) {
        $textoPublicar .= ', e ' . $semNota . ' deles estão sem nota lançada e serão gravados como reprovados';
    }
    $textoPublicar .= '. Os autores passam a ver a situação e a pontuação, e serão avisados por e-mail e no aplicativo.';

    $textoReabrir = 'Reabrir o resultado? Os autores deixam de ver situação e pontuação até uma nova publicação.';
    $textoAcao = $estaPublicado ? $textoReabrir : $textoPublicar;
    // Segunda barreira contra o duplo clique: depois da confirmação o botão
    // é desabilitado. A primeira é a trava de linha do serviço.
    $aoEnviar = 'if (!confirm(' . json_encode($textoAcao) . ')) { return false; } this.querySelector(\'button\').disabled = true;';
    ?>
    <form method="post" action="<?php echo url('trabalhos/resultado/' . (int) $evento['id']); ?>" onsubmit="<?php echo htmlspecialchars($aoEnviar, ENT_QUOTES, 'UTF-8'); ?>"><?= campoCsrf() ?>
        <?php if ($estaPublicado): ?>
            <input type="hidden" name="acao" value="reabrir">
            <button type="submit">Reabrir resultado</button>
        <?php else: ?>
            <input type="hidden" name="acao" value="publicar">
            <button type="submit">Publicar resultado</button>
        <?php endif; ?>
    </form>
<?php endif; ?>
