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

        <?php if (!empty($gamificacaoAtiva)): ?>
            <?php /* Fase 58: cartão de pontos. Só o total da própria pessoa;
            a posição e a classificação ficam em "Minha pontuação". */ ?>
            <div class="admin-card">
                <p>
                    <?php if ($totalPontos === null): ?>
                        <strong>Seus pontos na gincana</strong>
                        <br><small>O total não pôde ser calculado agora. Tente de novo em alguns instantes.</small>
                    <?php else: ?>
                        <strong>Seus pontos na gincana: <?php echo (int) $totalPontos; ?></strong>
                    <?php endif; ?>
                    <?php if (!empty($gincanaEncerrada)): ?>
                        <br><span class="status-pill laranja">Gincana encerrada: a classificação está congelada.</span>
                    <?php endif; ?>
                    <br>
                    <a href="<?php echo url('eventoApp/pontuacao/' . (int) $evento['id']); ?>">Ver minha pontuação e a classificação</a>
                    &middot;
                    <a href="<?php echo url('eventoApp/regras/' . (int) $evento['id']); ?>">Regras do jogo</a>
                </p>
            </div>
        <?php endif; ?>

        <p>
            <a href="<?php echo url('eventoApp/atividades/' . (int) $evento['id']); ?>" class="btn">Atividades</a>
        </p>

        <?php /* Fase 58: atalho para o leitor de códigos fixos. Antes, a
        única entrada era a tela de Atividades; agora o mesmo leitor também
        confirma o credenciamento no local e a participação em competições. */ ?>
        <p>
            <a href="<?php echo url('eventoApp/presenca/' . (int) $evento['id']); ?>" class="btn">Ler código</a>
            <br>
            <small>Presença em atividade, credenciamento no local e participação em competição.</small>
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

        <?php if (!empty($divulgacaoAtiva)): ?>
            <?php /* Fase 56: "Divulgação" só aparece com o módulo ligado no
            evento. O resumo vem protegido contra falha de banco, como os de
            Estandes e Conexões. */ ?>
            <p>
                <a href="<?php echo url('eventoApp/divulgacao/' . (int) $evento['id']); ?>" class="btn">Divulgação</a>
                <?php if ((int) $resumoDivulgacao['total_comprovacoes'] > 0): ?>
                    <br>
                    <small>
                        <?php echo (int) $resumoDivulgacao['total_pontos']; ?> <?php echo (int) $resumoDivulgacao['total_pontos'] === 1 ? 'ponto' : 'pontos'; ?>
                        em <?php echo (int) $resumoDivulgacao['total_comprovacoes']; ?> <?php echo (int) $resumoDivulgacao['total_comprovacoes'] === 1 ? 'comprovação enviada' : 'comprovações enviadas'; ?>
                    </small>
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

        <?php if (!empty($bonusAtivo) && !empty($progressoBonus)): ?>
            <?php /* Fase 57: o bloco percorre os bonus ATIVOS do evento, que
            sao cadastro e nao codigo - bonus novo cadastrado pela
            organizacao aparece aqui sem alteracao nenhuma nesta view. O
            bonus do tipo "responder a pesquisa" vira o botao da pesquisa,
            porque o que a pessoa precisa fazer ali nao e' esperar, e' abrir
            a tela. */ ?>
            <div class="admin-card">
                <p><strong>Bônus</strong></p>

                <?php foreach ($progressoBonus as $bonus): ?>
                    <?php
                    $credito = $bonus['credito'];
                    $ganho = $credito !== null && $credito['anulado_em'] === null;
                    $anulado = $credito !== null && $credito['anulado_em'] !== null;
                    $exigencia = (int) $bonus['exigencia'];
                    $atual = (int) $bonus['atual'];
                    $percentual = $exigencia > 0 ? min(100, (int) round(($atual / $exigencia) * 100)) : 0;
                    ?>
                    <p>
                        <strong><?php echo htmlspecialchars($bonus['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <?php if ($ganho): ?>
                            <span class="status-pill verde"><?php echo (int) $credito['pontos']; ?> <?php echo (int) $credito['pontos'] === 1 ? 'ponto' : 'pontos'; ?></span>
                        <?php elseif ($anulado): ?>
                            <span class="status-pill vermelho">Anulado pela organização</span>
                        <?php endif; ?>

                        <?php if (!empty($bonus['descricao'])): ?>
                            <br><small><?php echo htmlspecialchars($bonus['descricao'], ENT_QUOTES, 'UTF-8'); ?></small>
                        <?php endif; ?>

                        <?php if ($anulado && !empty($credito['motivo_anulacao'])): ?>
                            <br><small>Motivo: <?php echo htmlspecialchars($credito['motivo_anulacao'], ENT_QUOTES, 'UTF-8'); ?></small>
                        <?php endif; ?>

                        <?php if ($bonus['tipo'] === 'responder_pesquisa'): ?>
                            <?php if (!$ganho && !empty($pesquisaAberta) && empty($pesquisaRespondida)): ?>
                                <br>
                                <a href="<?php echo url('eventoApp/pesquisa/' . (int) $evento['id']); ?>" class="btn">Responder à pesquisa</a>
                                <?php if (empty($gincanaEncerrada)): ?>
                                    <br><small>Vale <?php echo (int) $bonus['pontos']; ?> <?php echo (int) $bonus['pontos'] === 1 ? 'ponto' : 'pontos'; ?>.</small>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php elseif (!empty($bonus['acao_participante'])): ?>
                            <?php /* Fase 58: ações do participante. Em vez de barra,
                            a tela diz o que fazer e leva à tela certa. */ ?>
                            <?php if (!$ganho && !$anulado && empty($gincanaEncerrada)): ?>
                                <br>
                                <?php if ($bonus['tipo'] === 'perfil_campos'): ?>
                                    <small>
                                        <?php if (!empty($bonus['campos_faltantes'])): ?>
                                            Falta preencher: <?php echo htmlspecialchars(implode(', ', $bonus['campos_faltantes']), ENT_QUOTES, 'UTF-8'); ?>.
                                        <?php else: ?>
                                            Seu perfil já tem tudo o que este bônus pede.
                                        <?php endif; ?>
                                        Vale <?php echo (int) $bonus['pontos']; ?> <?php echo (int) $bonus['pontos'] === 1 ? 'ponto' : 'pontos'; ?>.
                                    </small>
                                    <br><a href="<?php echo url('eventoAppPerfil/index/' . (int) $evento['id']); ?>">Completar meu perfil</a>
                                <?php elseif ($bonus['tipo'] === 'credenciamento_local'): ?>
                                    <small>Leia o código do credenciamento, afixado no local do evento. Vale <?php echo (int) $bonus['pontos']; ?> <?php echo (int) $bonus['pontos'] === 1 ? 'ponto' : 'pontos'; ?>.</small>
                                    <br><a href="<?php echo url('eventoApp/presenca/' . (int) $evento['id']); ?>">Ler código</a>
                                <?php elseif ($bonus['tipo'] === 'trabalho_submetido'): ?>
                                    <small>Vale para quem é autor de um trabalho submetido neste evento. Vale <?php echo (int) $bonus['pontos']; ?> <?php echo (int) $bonus['pontos'] === 1 ? 'ponto' : 'pontos'; ?>.</small>
                                    <?php if (empty($ehAvaliadorDoEvento)): ?>
                                        <br><a href="<?php echo url('trabalho/formulario/' . (int) $evento['id']); ?>">Submeter trabalho</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <small>Vale <?php echo (int) $bonus['pontos']; ?> <?php echo (int) $bonus['pontos'] === 1 ? 'ponto' : 'pontos'; ?>.</small>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php elseif (!$ganho && !$anulado && empty($gincanaEncerrada)): ?>
                            <br>
                            <small><?php echo $atual; ?> de <?php echo $exigencia; ?> <?php echo htmlspecialchars($bonus['unidade'], ENT_QUOTES, 'UTF-8'); ?><?php echo !empty($bonus['tipo_atividade_nome']) ? ' (' . htmlspecialchars($bonus['tipo_atividade_nome'], ENT_QUOTES, 'UTF-8') . ')' : ''; ?>, valendo <?php echo (int) $bonus['pontos']; ?> <?php echo (int) $bonus['pontos'] === 1 ? 'ponto' : 'pontos'; ?></small>
                            <span class="app-progresso-barra-fundo">
                                <span class="app-progresso-barra-preenchimento" style="width: <?php echo $percentual; ?>%"></span>
                            </span>
                        <?php endif; ?>
                    </p>
                <?php endforeach; ?>
            </div>
        <?php elseif (!empty($pesquisaAberta) && empty($pesquisaRespondida)): ?>
            <?php /* Fase 57: com a pesquisa aberta e sem bonus cadastrado
            para ela, o botao aparece assim mesmo - responder continua
            valendo a pena para quem organiza, so' nao credita pontos. */ ?>
            <p>
                <a href="<?php echo url('eventoApp/pesquisa/' . (int) $evento['id']); ?>" class="btn">Responder à pesquisa</a>
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

        <?php if (!empty($certificadosAbertos)): ?>
            <?php /* Fase 59: o botão aparece quando as duas travas da emissão
            estão satisfeitas (o evento terminou e a organização abriu a
            chave). A própria tela dos certificados diz, com os números da
            pessoa, o que falta a quem ainda não tem direito a nenhum: por
            isso o botão não depende de apurar o direito aqui, o que custaria
            várias consultas na tela mais aberta do aplicativo. */ ?>
            <p>
                <a href="<?php echo url('eventoApp/certificados/' . (int) $evento['id']); ?>" class="btn">Meus certificados</a>
            </p>
        <?php endif; ?>
    </div>
</div>
