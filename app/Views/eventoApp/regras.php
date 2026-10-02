<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 58: "Regras do jogo" - montada a partir do cadastro de cada módulo,
 * nunca de texto fixo. O texto de abertura é o da configuração da
 * Gamificação; o resto sai dos valores que o sistema de fato credita.
 */
$minutos = $config['minutos_pontualidade'];
$plural = function ($numero, $singular, $pluralTexto) {
    return (int) $numero . ' ' . ((int) $numero === 1 ? $singular : $pluralTexto);
};
$tiposComPontos = array_filter($tipos, function ($tipo) {
    return (int) $tipo['pontos_presenca'] > 0 || (int) $tipo['pontos_pontualidade'] > 0;
});
?>

<div class="site-page">
    <?php
    $eventoId = $evento['id'];
    $tituloTopo = $evento['nome'];
    $urlVoltar = url('eventoApp/pontuacao/' . (int) $eventoId);
    require __DIR__ . '/_app_bar.php';
    ?>

    <div class="site-form-page">
        <?php require __DIR__ . '/_ajuda_card.php'; ?>
        <h2>Regras do jogo</h2>

        <?php if (!empty($gincanaEncerrada)): ?>
            <p class="status-pill laranja">A gincana foi encerrada: nada mais pontua, e a classificação está congelada.</p>
        <?php elseif (!empty($config['encerramento_em'])): ?>
            <p class="status-pill laranja">A gincana encerra em <?php echo htmlspecialchars(formatarDataHora($config['encerramento_em']), ENT_QUOTES, 'UTF-8'); ?>. Depois disso, nada mais pontua.</p>
        <?php endif; ?>

        <?php if (!empty($config['texto_regras_html'])): ?>
            <div class="admin-card"><?php echo sanitizarHtmlRico((string) $config['texto_regras_html']); ?></div>
        <?php endif; ?>

        <div class="admin-card">
            <p><strong>Presença em atividades</strong></p>
            <?php if (empty($tiposComPontos) && empty($atividadesComExcecao)): ?>
                <p>Nenhuma atividade vale pontos de presença por enquanto.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($tiposComPontos as $tipo): ?>
                        <li>
                            <?php echo htmlspecialchars($tipo['nome'], ENT_QUOTES, 'UTF-8'); ?>:
                            <?php echo $plural($tipo['pontos_presenca'], 'ponto', 'pontos'); ?>
                            <?php if ($minutos !== null && (int) $tipo['pontos_pontualidade'] > 0): ?>
                                , mais <?php echo (int) $tipo['pontos_pontualidade']; ?> para quem confirma presença até <?php echo $plural($minutos, 'minuto', 'minutos'); ?> antes do início
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                    <?php foreach ($atividadesComExcecao as $atividade): ?>
                        <li>
                            <?php echo htmlspecialchars($atividade['nome'], ENT_QUOTES, 'UTF-8'); ?>
                            (<?php echo htmlspecialchars(formatarDataHora($atividade['data_inicio']), ENT_QUOTES, 'UTF-8'); ?>):
                            <?php echo $plural($atividade['valores']['pontos_presenca'], 'ponto', 'pontos'); ?>
                            <?php if ($minutos !== null && (int) $atividade['valores']['pontos_pontualidade'] > 0): ?>
                                , mais <?php echo (int) $atividade['valores']['pontos_pontualidade']; ?> de pontualidade
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p><small>
                    Confirme a presença lendo, com "Ler código", o código afixado no local da atividade. Quem participa
                    pela internet digita o código que o facilitador informa ao abrir a sala virtual, e pontua do mesmo
                    jeito. A confirmação abre antes do início e fecha no fim da atividade. Cada atividade pontua uma vez
                    por pessoa, e quem conduz a atividade não pontua nela.
                </small></p>
            <?php endif; ?>
        </div>

        <?php if ((int) $credenciamento['ativo'] === 1): ?>
            <div class="admin-card">
                <p><strong>Credenciamento no local</strong></p>
                <p>
                    Ao chegar, leia com "Ler código" o código do credenciamento afixado no local do evento.
                    <?php if (!empty($credenciamento['leitura_inicio']) && !empty($credenciamento['leitura_fim'])): ?>
                        Vale de <?php echo htmlspecialchars(formatarDataHora($credenciamento['leitura_inicio']), ENT_QUOTES, 'UTF-8'); ?>
                        a <?php echo htmlspecialchars(formatarDataHora($credenciamento['leitura_fim']), ENT_QUOTES, 'UTF-8'); ?>.
                    <?php endif; ?>
                    Os pontos estão em "Bônus e ações", abaixo. Só vale na presença física: não há código para quem
                    acompanha pela internet.
                </p>
            </div>
        <?php endif; ?>

        <?php if (!empty($competicoes)): ?>
            <div class="admin-card">
                <p><strong>Competições</strong></p>
                <p><small>Quem participa lê, com "Ler código", o código que o responsável mostra na hora. Cada competição pontua uma vez por pessoa, sem inscrição prévia. Quem só assiste pontua pela presença na atividade.</small></p>
                <?php foreach ($competicoes as $competicao): ?>
                    <p>
                        <strong><?php echo htmlspecialchars($competicao['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>:
                        <?php echo $plural($competicao['pontos_participacao'], 'ponto', 'pontos'); ?> por participação
                        <?php if (!empty($competicao['atividade_nome'])): ?>
                            <br><small><?php echo htmlspecialchars($competicao['atividade_nome'], ENT_QUOTES, 'UTF-8'); ?>, <?php echo htmlspecialchars(formatarDataHora($competicao['atividade_inicio']), ENT_QUOTES, 'UTF-8'); ?></small>
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($competicao['regras_html'])): ?>
                        <div><?php echo sanitizarHtmlRico((string) $competicao['regras_html']); ?></div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($bonus)): ?>
            <div class="admin-card">
                <p><strong>Bônus e ações</strong></p>
                <ul>
                    <?php foreach ($bonus as $item): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>:
                            <?php echo $plural($item['pontos'], 'ponto', 'pontos'); ?>, uma vez, por
                            <?php if ($item['tipo'] === 'atividades_distintas'): ?>
                                presença em <?php echo $plural($item['exigencia'], 'atividade diferente', 'atividades diferentes'); ?>
                            <?php elseif ($item['tipo'] === 'dias_distintos'): ?>
                                presença em <?php echo $plural($item['exigencia'], 'dia diferente', 'dias diferentes'); ?> do evento
                            <?php elseif ($item['tipo'] === 'atividades_do_tipo'): ?>
                                presença em <?php echo $plural($item['exigencia'], 'atividade', 'atividades'); ?> do tipo <?php echo htmlspecialchars((string) $item['tipo_atividade_nome'], ENT_QUOTES, 'UTF-8'); ?>
                            <?php elseif ($item['tipo'] === 'responder_pesquisa'): ?>
                                responder à pesquisa de satisfação
                            <?php elseif ($item['tipo'] === 'inscricao_evento'): ?>
                                ter inscrição no evento
                            <?php elseif ($item['tipo'] === 'perfil_campos'): ?>
                                <?php
                                $camposRotulos = [];
                                foreach (\App\Services\BonusApuracaoService::camposDoBonus($item) as $campo) {
                                    $camposRotulos[] = \App\Services\BonusApuracaoService::CAMPOS_PERFIL_ROTULOS[$campo];
                                }
                                ?>
                                preencher no perfil: <?php echo htmlspecialchars(implode(', ', $camposRotulos), ENT_QUOTES, 'UTF-8'); ?>
                            <?php elseif ($item['tipo'] === 'credenciamento_local'): ?>
                                confirmar o credenciamento no local
                            <?php elseif ($item['tipo'] === 'trabalho_submetido'): ?>
                                ser autor de um trabalho submetido neste evento
                            <?php endif; ?>
                            <?php if (!empty($item['descricao'])): ?>
                                <br><small><?php echo htmlspecialchars($item['descricao'], ENT_QUOTES, 'UTF-8'); ?></small>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ((int) $conexoes['ativo'] === 1 && (int) $conexoes['pontos_por_conexao'] > 0): ?>
            <div class="admin-card">
                <p><strong>Conexões</strong></p>
                <p>
                    <?php echo $plural($conexoes['pontos_por_conexao'], 'ponto', 'pontos'); ?> para cada pessoa, por conexão:
                    uma leitura do código de outro participante conecta e pontua as duas, uma única vez por dupla.
                    <?php if ((int) $conexoes['teto_conexoes_pontuadas'] > 0): ?>
                        Pontuam as primeiras <?php echo (int) $conexoes['teto_conexoes_pontuadas']; ?> conexões de cada pessoa.
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>

        <?php
        $estandesComPontos = array_filter($estandes, function ($estande) {
            return (int) $estande['pontos_visita'] > 0;
        });
        ?>
        <?php if (!empty($estandesComPontos)): ?>
            <div class="admin-card">
                <p><strong>Visitas a estandes</strong></p>
                <ul>
                    <?php foreach ($estandesComPontos as $estande): ?>
                        <li><?php echo htmlspecialchars($estande['nome'], ENT_QUOTES, 'UTF-8'); ?>: <?php echo $plural($estande['pontos_visita'], 'ponto', 'pontos'); ?></li>
                    <?php endforeach; ?>
                </ul>
                <p><small>Leia o código de cada estande, uma vez por estande.</small></p>
            </div>
        <?php endif; ?>

        <?php if ((int) $divulgacao['ativo'] === 1 && !empty($redes)): ?>
            <div class="admin-card">
                <p><strong>Divulgação</strong></p>
                <ul>
                    <?php foreach ($redes as $chaveRede => $rede): ?>
                        <?php if ((int) $rede['publicacao_ativa'] === 1): ?>
                            <li>
                                Publicação sobre o evento <?php echo $chaveRede === 'qualquer' ? 'em qualquer rede, com a imagem da tela' : htmlspecialchars($rede['rotulo_no'], ENT_QUOTES, 'UTF-8'); ?>:
                                <?php echo $plural($rede['publicacao_pontos'], 'ponto', 'pontos'); ?> por publicação
                                <?php if ((int) $rede['publicacao_teto_evento'] === 1): ?>
                                    , uma vez no evento
                                <?php elseif ((int) $rede['publicacao_teto_evento'] > 1): ?>
                                    , até <?php echo (int) $rede['publicacao_teto_evento']; ?> no evento
                                <?php endif; ?>
                                <?php if ((int) $rede['publicacao_teto_dia'] > 0): ?>
                                    , até <?php echo (int) $rede['publicacao_teto_dia']; ?> por dia
                                <?php endif; ?>
                            </li>
                        <?php endif; ?>
                        <?php if ((int) $rede['acompanhar_ativa'] === 1): ?>
                            <li>
                                Seguir <?php echo !empty($rede['acompanhar_canal_nome']) ? htmlspecialchars($rede['acompanhar_canal_nome'], ENT_QUOTES, 'UTF-8') : 'o canal indicado'; ?>
                                <?php echo htmlspecialchars($rede['rotulo_no'], ENT_QUOTES, 'UTF-8'); ?>:
                                <?php echo $plural($rede['acompanhar_pontos'], 'ponto', 'pontos'); ?>, uma vez
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                <p><small>A comprovação é enviada na tela Divulgação. Não há conferência antes do crédito, e a organização pode auditar e anular, com justificativa.</small></p>
            </div>
        <?php endif; ?>

        <div class="admin-card">
            <p><strong>Classificação</strong></p>
            <p>
                A classificação soma todos os pontos válidos, na hora em que você a abre. Você sempre vê a sua posição
                e o seu total.
                <?php if ((int) $config['classificacao_visivel'] === 1 && (int) $config['classificacao_quantidade'] > 0): ?>
                    A tela "Minha pontuação" mostra também os <?php echo (int) $config['classificacao_quantidade']; ?> primeiros<?php echo (int) $config['classificacao_mostrar_nomes'] === 1 ? ', com nome' : ', sem nome'; ?>.
                <?php endif; ?>
            </p>
            <?php if (!empty($desempate)): ?>
                <p>Em caso de empate no total, vale, nesta ordem:</p>
                <ol>
                    <?php foreach ($desempate as $criterio): ?>
                        <li><?php echo htmlspecialchars($criterio, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ol>
                <p><small>Se ainda houver empate, as pessoas dividem a posição.</small></p>
            <?php else: ?>
                <p>Em caso de empate no total, as pessoas dividem a posição.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
