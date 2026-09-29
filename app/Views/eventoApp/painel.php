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

        <?php if (!empty($conexoesAtivas)): ?>
            <?php /* Fase 55: a antiga "Ler código" virou "Conectar com
            participante" e passou a gravar a conexão e pontuar os dois
            lados, então só aparece com o módulo ligado no evento. O resumo
            vem protegido contra falha de banco, como o de Estandes. */ ?>
            <p>
                <a href="<?php echo url('eventoApp/ler/' . (int) $evento['id']); ?>" class="btn">Conectar com participante</a>
                <?php if ((int) $resumoConexoes['total_conexoes'] > 0): ?>
                    <br>
                    <small>
                        <?php echo (int) $resumoConexoes['total_pontos']; ?> <?php echo (int) $resumoConexoes['total_pontos'] === 1 ? 'ponto' : 'pontos'; ?>
                        em <?php echo (int) $resumoConexoes['total_conexoes']; ?> <?php echo (int) $resumoConexoes['total_conexoes'] === 1 ? 'pessoa conectada' : 'pessoas conectadas'; ?>
                    </small>
                    <br>
                    <a href="<?php echo url('eventoApp/conexoes/' . (int) $evento['id']); ?>">Ver minhas conexões</a>
                <?php endif; ?>
            </p>
        <?php endif; ?>

        <?php if (!empty($temEstandes)): ?>
            <?php /* Fase 54: so' aparece com estande ativo no evento; o
            resumo de pontos vem protegido contra falha de banco. */ ?>
            <p>
                <a href="<?php echo url('eventoApp/estandes/' . (int) $evento['id']); ?>" class="btn">Estandes</a>
                <?php if (!empty($resumoEstandes['visitas'])): ?>
                    <br>
                    <small>
                        <?php echo (int) $resumoEstandes['total_pontos']; ?> <?php echo (int) $resumoEstandes['total_pontos'] === 1 ? 'ponto' : 'pontos'; ?>
                        em <?php echo count($resumoEstandes['visitas']); ?> <?php echo count($resumoEstandes['visitas']) === 1 ? 'estande visitado' : 'estandes visitados'; ?>
                    </small>
                <?php endif; ?>
            </p>
        <?php endif; ?>

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

        <?php if (!empty($anais)): ?>
            <?php /* Fase 53: os Anais publicados, para qualquer inscrito
            (autor, avaliador ou visitante do evento). O PDF é um arquivo
            público; abre em outra aba para não prender a pessoa dentro do
            aplicativo instalado, que não tem botão de voltar para um PDF. */ ?>
            <p>
                <a href="<?php echo htmlspecialchars($anais['url'], ENT_QUOTES, 'UTF-8'); ?>" class="btn" target="_blank" rel="noopener">Anais</a>
                <br>
                <small>
                    <?php echo htmlspecialchars($anais['titulo'], ENT_QUOTES, 'UTF-8'); ?>
                    <?php echo $anais['rotulo_identificador'] !== '' ? ' - ' . htmlspecialchars($anais['rotulo_identificador'], ENT_QUOTES, 'UTF-8') : ''; ?>
                </small>
                <?php if (!empty($anais['descricao'])): ?>
                    <br><small><?php echo htmlspecialchars($anais['descricao'], ENT_QUOTES, 'UTF-8'); ?></small>
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
</div>
