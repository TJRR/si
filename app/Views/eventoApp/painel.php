<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>

<div class="site-page">
    <?php
    $eventoId = $evento['id'];
    $tituloTopo = $evento['nome'];
    require __DIR__ . '/_app_bar.php';
    ?>

    <div class="site-form-page">
        <?php require __DIR__ . '/_ajuda_card.php'; ?>
        <p>
            <span class="status-pill <?php echo $inscricao['homologado_em'] !== null ? 'verde' : 'laranja'; ?>">
                <?php echo $inscricao['homologado_em'] !== null ? 'Inscrição confirmada' : 'Aguardando homologação'; ?>
            </span>
        </p>

        <p>
            <a href="<?php echo url('eventoApp/atividades/' . (int) $evento['id']); ?>" class="btn">Atividades</a>
        </p>

        <p>
            <a href="<?php echo url('eventoApp/ler/' . (int) $evento['id']); ?>" class="btn">Ler código</a>
        </p>

        <?php if ($ehAvaliadorDoEvento): ?>
            <?php /* Fase 49B, achado do usuário: quem é avaliador avulso
            deste evento vê só o acesso à avaliação, nunca os botões de
            submissão - a exclusão mútua no backend já impede as duas
            coisas ao mesmo tempo, a interface deixa de oferecer um botão
            que sempre resultaria em erro. */ ?>
            <p>
                <a href="<?php echo url('avaliacaoTrabalhos/index'); ?>" class="btn">Avaliar trabalhos</a>
            </p>
        <?php else: ?>
            <?php /* Fase 49B: ponto de entrada da submissão de Trabalhos
            dentro do aplicativo, confirmado pelo usuário - mesmo padrão
            dos links acima, a própria tela de destino decide o que
            mostrar (formulário, aviso de indisponível, ou os trabalhos já
            enviados). */ ?>
            <p>
                <a href="<?php echo url('trabalho/formulario/' . (int) $evento['id']); ?>" class="btn">Submeter trabalho</a>
            </p>

            <p>
                <a href="<?php echo url('trabalho/meusTrabalhos'); ?>" class="btn">Meus trabalhos</a>
            </p>
        <?php endif; ?>
    </div>
</div>
