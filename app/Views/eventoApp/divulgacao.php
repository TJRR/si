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
                <?php echo htmlspecialchars($janelaTexto, ENT_QUOTES, 'UTF-8'); ?>.
            </p>
        <?php elseif (!empty($gincanaEncerrada)): ?>
            <p class="status-pill laranja">A gincana deste evento foi encerrada: comprovações de divulgação não pontuam mais.</p>
        <?php else: ?>
            <?php
            // Fase 58: a conta exigida depende da rede (rede do perfil,
            // telefone marcado como WhatsApp, ou nenhuma em "Qualquer rede"),
            // e só vale para publicação: seguir um canal nunca exige conta.
            $faltaConta = function ($chave, array $rede) use ($redesDaPessoa, $temWhatsapp) {
                if ($rede['publicacao_ativa'] !== 1 || $rede['conta'] === null) {
                    return false;
                }

                return $rede['conta'] === 'whatsapp' ? empty($temWhatsapp) : !isset($redesDaPessoa[$chave]);
            };
            $faltamRedes = false;
            foreach ($redes as $chave => $rede) {
                $faltamRedes = $faltamRedes || $faltaConta($chave, $rede);
            }
            ?>
            <?php if ($faltamRedes): ?>
                <p>
                    Publicação numa rede específica precisa ser da conta que você cadastrou.
                    <a href="<?php echo url('eventoAppPerfil/index/' . (int) $evento['id']); ?>">Informe as suas contas em Meu Perfil</a>
                    antes de enviar. Em "Qualquer rede", basta a imagem da tela.
                </p>
            <?php endif; ?>

            <?php
            /* A tela só pergunta o que tem mais de uma resposta possível. Com uma
            rede só ligada no evento, ou com uma ação só, a escolha vai num campo
            escondido. Os campos de prova aparecem
            conforme o que aquela combinação aceita: rede que só aceita imagem não
            mostra campo de endereço. Quando ainda há escolha a fazer, os dois
            campos de prova aparecem, como antes, e o servidor recusa o que não
            servir. Nenhuma rotina de tela nova: tudo é decidido no servidor. */
            $acoesPossiveis = [];

            foreach ($redes as $chave => $rede) {
                if ($rede['publicacao_ativa'] === 1) {
                    $acoesPossiveis['publicacao'] = 'Publiquei sobre o evento';
                }

                if ($rede['acompanhar_ativa'] === 1) {
                    $acoesPossiveis['acompanhar'] = 'Passei a seguir o canal indicado';
                }
            }

            $redeUnica = count($redes) === 1 ? key($redes) : null;
            $acaoUnica = count($acoesPossiveis) === 1 ? key($acoesPossiveis) : null;
            $redeEscolhida = $redeUnica !== null ? $redes[$redeUnica] : null;

            // Com rede e ação definidas, a tela sabe exatamente qual prova vale.
            $provaUnica = null;

            if ($redeEscolhida !== null && $acaoUnica !== null) {
                $provaUnica = $acaoUnica === 'publicacao' ? $redeEscolhida['publicacao_prova'] : $redeEscolhida['acompanhar_prova'];
            }

            $mostrarEndereco = $provaUnica === null || $provaUnica === 'endereco' || $provaUnica === 'ambos';
            $mostrarImagem = $provaUnica === null || $provaUnica === 'imagem' || $provaUnica === 'ambos';
            ?>
            <form method="post" action="<?php echo url('eventoApp/divulgacaoEnviar/' . (int) $evento['id']); ?>" enctype="multipart/form-data">
                <?php echo campoCsrf(); ?>

                <?php if ($redeUnica === null): ?>
                    <div class="campo">
                        <label for="rede">Rede social</label>
                        <select name="rede" id="rede" required>
                            <option value="">Escolha</option>
                            <?php foreach ($redes as $chave => $rede): ?>
                                <option value="<?php echo htmlspecialchars($chave, ENT_QUOTES, 'UTF-8'); ?>"<?php echo (isset($valores['rede']) && $valores['rede'] === $chave) ? ' selected' : ''; ?>>
                                    <?php echo htmlspecialchars($rede['rotulo'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php echo $faltaConta($chave, $rede) ? ' (informe a conta em Meu Perfil)' : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="rede" value="<?php echo htmlspecialchars($redeUnica, ENT_QUOTES, 'UTF-8'); ?>">
                <?php endif; ?>

                <?php if ($acaoUnica === null): ?>
                    <div class="campo">
                        <label for="tipo_acao">O que você está comprovando</label>
                        <select name="tipo_acao" id="tipo_acao" required>
                            <option value="">Escolha</option>
                            <?php foreach ($acoesPossiveis as $chaveAcao => $rotuloAcao): ?>
                                <option value="<?php echo htmlspecialchars($chaveAcao, ENT_QUOTES, 'UTF-8'); ?>"<?php echo (isset($valores['tipo_acao']) && $valores['tipo_acao'] === $chaveAcao) ? ' selected' : ''; ?>><?php echo htmlspecialchars($rotuloAcao, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="tipo_acao" value="<?php echo htmlspecialchars($acaoUnica, ENT_QUOTES, 'UTF-8'); ?>">
                <?php endif; ?>

                <?php if ($redeUnica === 'qualquer' || ($redeUnica === null && isset($redes['qualquer']))): ?>
                    <div class="campo">
                        <label for="rede_informada">Em que rede você publicou? (opcional)</label>
                        <input type="text" name="rede_informada" id="rede_informada" maxlength="60" placeholder="Por exemplo, a rede interna do seu órgão" value="<?php echo htmlspecialchars(isset($valores['rede_informada']) ? $valores['rede_informada'] : '', ENT_QUOTES, 'UTF-8'); ?>">
                        <small>Ajuda a organização a entender a sua comprovação.</small>
                    </div>
                <?php endif; ?>

                <?php if ($mostrarEndereco): ?>
                    <div class="campo">
                        <label for="endereco">Endereço da publicação</label>
                        <input type="text" name="endereco" id="endereco" maxlength="500" placeholder="Cole aqui o endereço da sua publicação" value="<?php echo htmlspecialchars(isset($valores['endereco']) ? $valores['endereco'] : '', ENT_QUOTES, 'UTF-8'); ?>"<?php echo $provaUnica === 'endereco' ? ' required' : ''; ?>>
                        <?php if ($provaUnica === null): ?>
                            <small>Preencha quando a rede escolhida aceitar o endereço da publicação.</small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($mostrarImagem): ?>
                    <div class="campo">
                        <label for="imagem">Imagem da tela</label>
                        <input type="file" name="imagem" id="imagem" accept="image/jpeg,image/png,image/webp"<?php echo $provaUnica === 'imagem' ? ' required' : ''; ?>>
                        <small>
                            Imagem em JPEG, PNG ou WebP, de até 4MB. A organização do evento vê esta imagem para
                            conferir a comprovação, então evite enviar captura com informação de outras pessoas
                            que você não queira compartilhar.
                        </small>
                    </div>
                <?php endif; ?>

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
                                Seguir
                                <?php if (!empty($rede['acompanhar_canal_nome'])): ?>
                                    <?php if (!empty($rede['acompanhar_canal_endereco']) && linkHttpValido($rede['acompanhar_canal_endereco'])): ?>
                                        <a href="<?php echo htmlspecialchars($rede['acompanhar_canal_endereco'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($rede['acompanhar_canal_nome'], ENT_QUOTES, 'UTF-8'); ?></a>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($rede['acompanhar_canal_nome'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    o canal indicado pela organização
                                <?php endif; ?>
                                <?php echo htmlspecialchars($rede['rotulo_no'], ENT_QUOTES, 'UTF-8'); ?>:
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
            $rotuloRede = \App\Repositories\DivulgacaoConfigRepository::rotuloDaRede($comprovacao['rede']);

            if (!empty($comprovacao['rede_informada'])) {
                $rotuloRede .= ' (' . $comprovacao['rede_informada'] . ')';
            }

            $anulada = $comprovacao['anulado_em'] !== null;
            ?>
            <div class="admin-card">
                <p>
                    <strong><?php echo htmlspecialchars($rotuloRede, ENT_QUOTES, 'UTF-8'); ?></strong>
                    &middot;
                    <?php echo $comprovacao['tipo_acao'] === 'acompanhar' ? 'Passei a seguir' : 'Publicação'; ?>
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
