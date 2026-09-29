<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 56: tela "Divulgacao" do participante - envio da comprovacao e a
 * situacao de cada uma.
 *
 * Os pontos sao creditados no envio e congelados no valor daquele momento,
 * por decisao do dono: nao ha conferencia antes do credito. A organizacao
 * analisa depois, e pode anular com justificativa, que aparece aqui na
 * propria linha.
 *
 * O endereco da publicacao e' conteudo digitado por pessoa: sai escapado,
 * em aba nova e sem repassar a pagina de origem, revalidado por
 * linkHttpValido(), mesmo padrao das demais telas (Fase 49).
 */
?>

<div class="site-page">
    <?php
    $eventoId = $evento['id'];
    $tituloTopo = $evento['nome'];
    $urlVoltar = url('eventoApp/index/' . (int) $eventoId);
    require __DIR__ . '/_app_bar.php';
    ?>

    <div class="site-form-page">
        <?php require __DIR__ . '/_ajuda_card.php'; ?>
        <h2>Divulgação</h2>

        <div class="admin-card">
            <p>
                <strong>Seus pontos em divulgação: <?php echo (int) $resumo['total_pontos']; ?></strong>
                <br>
                <?php echo (int) $resumo['total_comprovacoes']; ?>
                <?php echo (int) $resumo['total_comprovacoes'] === 1 ? 'comprovação enviada' : 'comprovações enviadas'; ?>
                <?php if ((int) $resumo['total_anuladas'] > 0): ?>
                    <br>
                    <small><?php echo (int) $resumo['total_anuladas']; ?> anulada(s) pela organização</small>
                <?php endif; ?>
            </p>
        </div>

        <?php if ((int) $config['ativo'] !== 1): ?>
            <p>A divulgação não está ativada neste evento.</p>
        <?php elseif (empty($redes)): ?>
            <p>Nenhuma rede social está valendo pontos neste evento por enquanto.</p>
        <?php elseif (!$dentroDaJanela): ?>
            <p>
                As comprovações de divulgação valem apenas
                <?php echo $janelaTexto !== '' ? 'de ' . htmlspecialchars($janelaTexto, ENT_QUOTES, 'UTF-8') : 'durante o evento'; ?>.
            </p>
        <?php else: ?>
            <?php $faltamRedes = false; ?>
            <?php foreach ($redes as $chave => $rede): ?>
                <?php $faltamRedes = $faltamRedes || !isset($redesDaPessoa[$chave]); ?>
            <?php endforeach; ?>
            <?php if ($faltamRedes): ?>
                <p>
                    A comprovação é conferida contra a conta que você cadastrou.
                    <a href="<?php echo url('eventoAppPerfil/index/' . (int) $evento['id']); ?>">Informe os seus perfis em Meu Perfil</a>
                    antes de enviar.
                </p>
            <?php endif; ?>

            <form method="post" action="<?php echo url('eventoApp/divulgacaoEnviar/' . (int) $evento['id']); ?>" enctype="multipart/form-data">
                <?php echo campoCsrf(); ?>

                <div class="campo">
                    <label for="rede">Rede social</label>
                    <select name="rede" id="rede" required>
                        <option value="">Escolha</option>
                        <?php foreach ($redes as $chave => $rede): ?>
                            <option value="<?php echo htmlspecialchars($chave, ENT_QUOTES, 'UTF-8'); ?>"<?php echo (isset($valores['rede']) && $valores['rede'] === $chave) ? ' selected' : ''; ?>>
                                <?php echo htmlspecialchars($rede['rotulo'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php echo !isset($redesDaPessoa[$chave]) ? ' (informe o seu perfil em Meu Perfil)' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="tipo_acao">O que você está comprovando</label>
                    <select name="tipo_acao" id="tipo_acao" required>
                        <option value="">Escolha</option>
                        <option value="publicacao"<?php echo (isset($valores['tipo_acao']) && $valores['tipo_acao'] === 'publicacao') ? ' selected' : ''; ?>>Publiquei sobre o evento</option>
                        <option value="acompanhar"<?php echo (isset($valores['tipo_acao']) && $valores['tipo_acao'] === 'acompanhar') ? ' selected' : ''; ?>>Passei a acompanhar o canal do Tribunal</option>
                    </select>
                </div>

                <div class="campo">
                    <label for="endereco">Endereço da publicação</label>
                    <input type="text" name="endereco" id="endereco" maxlength="500" placeholder="Cole aqui o endereço da sua publicação" value="<?php echo htmlspecialchars(isset($valores['endereco']) ? $valores['endereco'] : '', ENT_QUOTES, 'UTF-8'); ?>">
                    <small>Preencha quando a rede escolhida aceitar o endereço da publicação.</small>
                </div>

                <div class="campo">
                    <label for="imagem">Imagem da tela</label>
                    <input type="file" name="imagem" id="imagem" accept="image/jpeg,image/png,image/webp">
                    <small>
                        Imagem em JPEG, PNG ou WebP, de até 4MB. A organização do evento vê esta imagem para
                        conferir a comprovação, então evite enviar captura com informação de outras pessoas
                        que você não queira compartilhar.
                    </small>
                </div>

                <p>
                    <button type="submit" class="btn">Enviar comprovação</button>
                </p>
            </form>

            <div class="admin-card">
                <p><strong>O que vale neste evento</strong></p>
                <ul>
                    <?php foreach ($redes as $rede): ?>
                        <?php if ($rede['publicacao_ativa'] === 1): ?>
                            <li>
                                <?php echo htmlspecialchars($rede['rotulo'], ENT_QUOTES, 'UTF-8'); ?>, publicação sobre o evento:
                                <?php echo (int) $rede['publicacao_pontos']; ?> ponto(s) por publicação
                                <?php if ((int) $rede['publicacao_teto_dia'] > 0): ?>
                                    , até <?php echo (int) $rede['publicacao_teto_dia']; ?> por dia
                                <?php endif; ?>
                                <?php if ((int) $rede['publicacao_teto_evento'] > 0): ?>
                                    , até <?php echo (int) $rede['publicacao_teto_evento']; ?> no evento
                                <?php endif; ?>
                            </li>
                        <?php endif; ?>
                        <?php if ($rede['acompanhar_ativa'] === 1): ?>
                            <li>
                                <?php echo htmlspecialchars($rede['rotulo'], ENT_QUOTES, 'UTF-8'); ?>, passar a acompanhar o canal:
                                <?php echo (int) $rede['acompanhar_pontos']; ?> ponto(s), uma vez
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                <p>
                    <small>
                        Os pontos são os do momento do envio. Mudança na configuração do evento não altera o que
                        já foi creditado.
                    </small>
                </p>
            </div>
        <?php endif; ?>

        <h3>Comprovações enviadas</h3>

        <?php if (empty($comprovacoes)): ?>
            <p>Você ainda não enviou nenhuma comprovação neste evento.</p>
        <?php endif; ?>

        <?php foreach ($comprovacoes as $comprovacao): ?>
            <?php
            $rotuloRede = isset(\App\Repositories\UsuarioPerfilRepository::REDES_ROTULOS[$comprovacao['rede']])
                ? \App\Repositories\UsuarioPerfilRepository::REDES_ROTULOS[$comprovacao['rede']]
                : $comprovacao['rede'];
            $anulada = $comprovacao['anulado_em'] !== null;
            ?>
            <div class="admin-card">
                <p>
                    <strong><?php echo htmlspecialchars($rotuloRede, ENT_QUOTES, 'UTF-8'); ?></strong>
                    &middot;
                    <?php echo $comprovacao['tipo_acao'] === 'acompanhar' ? 'Passei a acompanhar' : 'Publicação'; ?>
                    &middot;
                    <?php echo date('d/m/Y H:i', strtotime($comprovacao['enviado_em'])); ?>
                </p>
                <p>
                    <?php if ($anulada): ?>
                        <span class="status-pill vermelho">Anulada pela organização</span>
                    <?php elseif ((int) $comprovacao['pontos_creditados'] > 0): ?>
                        <span class="status-pill verde"><?php echo (int) $comprovacao['pontos_creditados']; ?> ponto(s)</span>
                    <?php else: ?>
                        <span class="status-pill laranja">Registrada, sem pontos</span>
                    <?php endif; ?>
                </p>
                <?php if ($anulada && !empty($comprovacao['motivo_anulacao'])): ?>
                    <p>Motivo: <?php echo htmlspecialchars($comprovacao['motivo_anulacao'], ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
                <?php if (!empty($comprovacao['endereco']) && linkHttpValido($comprovacao['endereco'])): ?>
                    <p>
                        <a href="<?php echo htmlspecialchars($comprovacao['endereco'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Ver a publicação</a>
                    </p>
                <?php endif; ?>
                <?php if (!empty($comprovacao['arquivo_path'])): ?>
                    <p>
                        <a href="<?php echo url('eventoApp/divulgacaoImagem/' . (int) $evento['id'] . '/' . (int) $comprovacao['id']); ?>" target="_blank" rel="noopener noreferrer">Ver a imagem enviada</a>
                    </p>
                <?php elseif ($comprovacao['arquivo_removido_em'] !== null): ?>
                    <p><small>A imagem foi apagada conforme a política de retenção de dados do evento.</small></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
