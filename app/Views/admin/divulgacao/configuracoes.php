<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Configurações de Divulgação: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('divulgacao/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<?php if (!$podeEditar): ?>
    <p class="status-pill laranja">Somente leitura: só o Administrador altera estas configurações.</p>
<?php endif; ?>

<form method="post" action="<?php echo url('divulgacao/configuracoes/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
    <fieldset <?php echo $podeEditar ? '' : 'disabled'; ?>>
        <legend>Divulgação em redes sociais</legend>

        <label>
            <input type="checkbox" name="ativo" value="1" <?php echo (int) $config['ativo'] === 1 ? 'checked' : ''; ?>>
            Ativar a divulgação neste evento
        </label>
        <p style="color:#555;font-size:0.9em;">
            Com a divulgação desativada, o aplicativo não oferece o envio de comprovação e nenhuma pontuação nova
            é registrada. As comprovações já enviadas continuam visíveis para quem as enviou.
        </p>

        <label>A partir de
            <input type="date" name="data_inicio" value="<?php echo htmlspecialchars((string) $config['data_inicio'], ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <label>Até
            <input type="date" name="data_fim" value="<?php echo htmlspecialchars((string) $config['data_fim'], ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <p style="color:#555;font-size:0.9em;">
            Período em que as comprovações valem. Cada campo limita por conta própria, e em branco aquele lado
            não limita nada: com os dois em branco, a divulgação fica aberta enquanto estiver ativada, e quem
            decide liberar e fechar é a marca acima. Preencha para aceitar a divulgação feita antes da abertura
            do evento ou para dar prazo de envio depois do encerramento. O último dia conta inteiro.
        </p>

        <label>Apagar as imagens de comprovação automaticamente depois de quantos dias do fim do evento
            <input type="number" name="dias_retencao_imagens" min="0" max="3650" step="1" value="<?php echo $config['dias_retencao_imagens'] !== null ? (int) $config['dias_retencao_imagens'] : ''; ?>">
        </label>
        <p style="color:#555;font-size:0.9em;">
            Em branco, as imagens ficam guardadas até alguém apagá-las pela tela Comprovações. Preenchido, o sistema
            apaga sozinho, uma vez por dia, as imagens deste evento quando o prazo vence, contado a partir do último
            dia do evento. As comprovações e os pontos continuam registrados; só a imagem deixa de existir.
        </p>
    </fieldset>

    <?php /* Fase 58: a tela desenha só as redes que o Administrador incluiu
    neste evento, e cada uma mostra só o que aceita: Spotify e Flickr só
    "seguir", WhatsApp e "Qualquer rede" só publicação, e só com imagem. O
    servidor aplica as mesmas restrições ao gravar. */ ?>
    <?php if (empty($redes)): ?>
        <p>Nenhuma rede escolhida: o aplicativo não oferece envio de comprovação. Acrescente abaixo a rede que vale neste evento.</p>
    <?php endif; ?>

    <?php foreach ($redes as $chave => $rede): ?>
        <?php $campo = 'redes[' . htmlspecialchars($chave, ENT_QUOTES, 'UTF-8') . ']'; ?>
        <fieldset <?php echo $podeEditar ? '' : 'disabled'; ?>>
            <legend>
                <?php echo htmlspecialchars($rede['rotulo'], ENT_QUOTES, 'UTF-8'); ?>
                <?php if ($podeEditar): ?>
                    <?php /* O botão fica ligado por "form=" a um formulário declarado depois
                    do principal: HTML não aceita formulário dentro de formulário. Mesma saída
                    da tela de homologação de inscritos. */ ?>
                    <button type="submit" form="retirar-rede-<?php echo htmlspecialchars($chave, ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Retirar esta rede do evento">✕</button>
                <?php endif; ?>
            </legend>

            <?php if ($chave === 'qualquer'): ?>
                <p style="color:#555;font-size:0.9em;">
                    Publicação em qualquer rede, inclusive rede interna de outro órgão: basta a imagem da tela, sem
                    conferência da rede nem da conta. A pessoa pode informar o nome da rede. Quem publica numa rede
                    específica ligada abaixo também pode enviar por aqui.
                </p>
            <?php elseif ($chave === 'whatsapp'): ?>
                <p style="color:#555;font-size:0.9em;">A conta é o telefone do perfil marcado como WhatsApp. A prova é a imagem da tela.</p>
            <?php endif; ?>

            <?php if (!empty($rede['aceita_publicacao'])): ?>
                <label>
                    <input type="checkbox" name="<?php echo $campo; ?>[publicacao_ativa]" value="1" <?php echo $rede['publicacao_ativa'] === 1 ? 'checked' : ''; ?>>
                    Aceitar comprovação de publicação sobre o evento
                </label>

                <label>Pontos por publicação
                    <input type="number" name="<?php echo $campo; ?>[publicacao_pontos]" min="0" max="65535" value="<?php echo (int) $rede['publicacao_pontos']; ?>">
                </label>

                <?php if (!empty($rede['so_imagem'])): ?>
                    <p style="color:#555;font-size:0.9em;">Prova da publicação: só a imagem da tela.</p>
                <?php else: ?>
                    <label>O que a pessoa envia como prova da publicação
                        <select name="<?php echo $campo; ?>[publicacao_prova]">
                            <option value="ambos"<?php echo $rede['publicacao_prova'] === 'ambos' ? ' selected' : ''; ?>>Imagem da tela ou endereço da publicação</option>
                            <option value="imagem"<?php echo $rede['publicacao_prova'] === 'imagem' ? ' selected' : ''; ?>>Só a imagem da tela</option>
                            <option value="endereco"<?php echo $rede['publicacao_prova'] === 'endereco' ? ' selected' : ''; ?>>Só o endereço da publicação</option>
                        </select>
                    </label>
                <?php endif; ?>

                <label>Limite de publicações que pontuam por dia
                    <input type="number" name="<?php echo $campo; ?>[publicacao_teto_dia]" min="0" max="65535" value="<?php echo (int) $rede['publicacao_teto_dia']; ?>">
                </label>

                <label>Limite de publicações que pontuam no evento inteiro
                    <input type="number" name="<?php echo $campo; ?>[publicacao_teto_evento]" min="0" max="65535" value="<?php echo (int) $rede['publicacao_teto_evento']; ?>">
                </label>

                <p style="color:#555;font-size:0.9em;">
                    Zero em um limite significa sem limite naquela contagem; os dois preenchidos valem juntos.
                    Passado o limite, a comprovação continua sendo registrada, só que sem pontos, e a tela avisa
                    isso à pessoa. Para "vale uma vez só", use limite 1 no evento inteiro.
                </p>
            <?php endif; ?>

            <?php if (!empty($rede['aceita_acompanhar'])): ?>
                <label>
                    <input type="checkbox" name="<?php echo $campo; ?>[acompanhar_ativa]" value="1" <?php echo $rede['acompanhar_ativa'] === 1 ? 'checked' : ''; ?>>
                    Aceitar comprovação de que passou a seguir o canal indicado nesta rede
                </label>

                <label>Nome do canal
                    <input type="text" name="<?php echo $campo; ?>[acompanhar_canal_nome]" maxlength="100" placeholder="Por exemplo, o nome do canal oficial do evento" value="<?php echo htmlspecialchars((string) $rede['acompanhar_canal_nome'], ENT_QUOTES, 'UTF-8'); ?>">
                </label>

                <label>Endereço do canal
                    <input type="text" name="<?php echo $campo; ?>[acompanhar_canal_endereco]" maxlength="255" placeholder="https://" value="<?php echo htmlspecialchars((string) $rede['acompanhar_canal_endereco'], ENT_QUOTES, 'UTF-8'); ?>">
                </label>

                <label>Pontos por passar a seguir
                    <input type="number" name="<?php echo $campo; ?>[acompanhar_pontos]" min="0" max="65535" value="<?php echo (int) $rede['acompanhar_pontos']; ?>">
                </label>

                <label>O que a pessoa envia como prova de que segue
                    <select name="<?php echo $campo; ?>[acompanhar_prova]">
                        <option value="imagem"<?php echo $rede['acompanhar_prova'] === 'imagem' ? ' selected' : ''; ?>>Só a imagem da tela</option>
                        <option value="ambos"<?php echo $rede['acompanhar_prova'] === 'ambos' ? ' selected' : ''; ?>>Imagem da tela ou endereço</option>
                        <option value="endereco"<?php echo $rede['acompanhar_prova'] === 'endereco' ? ' selected' : ''; ?>>Só o endereço</option>
                    </select>
                </label>

                <p style="color:#555;font-size:0.9em;">
                    Seguir pontua uma única vez por rede, e não exige a conta da rede no perfil da pessoa. O nome e o
                    endereço do canal aparecem para o participante; o endereço só fica clicável se começar por http
                    ou https. Deixe a prova em "só a imagem": com endereço, todos colariam o mesmo endereço do canal, e
                    só o primeiro pontuaria, porque cada endereço vale uma vez no evento.
                </p>
            <?php endif; ?>
        </fieldset>
    <?php endforeach; ?>

    <p style="color:#555;font-size:0.9em;">
        Mudar pontos, limites ou período vale só para as próximas comprovações: o que já foi creditado não muda.
        Deixar uma rede ativa com pontos zerados é combinação válida, e registra a comprovação sem creditar
        ponto nenhum; para não registrar nada, deixe a rede desativada.
    </p>

    <?php if ($podeEditar && !empty($redes)): ?>
    <div class="form-acoes">
        <button type="submit">Salvar</button>
    </div>
    <?php endif; ?>
</form>

<?php if ($podeEditar): ?>
    <?php foreach ($redes as $chave => $rede): ?>
        <form method="post" id="retirar-rede-<?php echo htmlspecialchars($chave, ENT_QUOTES, 'UTF-8'); ?>"
              action="<?php echo url('divulgacao/retirarRede/' . (int) $evento['id']); ?>"
              onsubmit="return confirm('Retirar <?php echo htmlspecialchars(addslashes($rede['rotulo']), ENT_QUOTES, 'UTF-8'); ?> deste evento? Pontos e limites ficam guardados para o caso de ela voltar, e as comprovações já enviadas continuam valendo com os pontos daquele momento.');"><?= campoCsrf() ?>
            <input type="hidden" name="rede" value="<?php echo htmlspecialchars($chave, ENT_QUOTES, 'UTF-8'); ?>">
        </form>
    <?php endforeach; ?>

    <?php if (!empty($disponiveis)): ?>
        <h2>Acrescentar rede</h2>
        <form method="post" action="<?php echo url('divulgacao/incluirRede/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
            <label>Rede
                <select name="rede" required>
                    <?php foreach ($disponiveis as $chave => $rotulo): ?>
                        <option value="<?php echo htmlspecialchars($chave, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit">Acrescentar ao evento</button>
        </form>
        <p style="color:#555;font-size:0.9em;">
            A rede acrescentada entra desligada: marque o que ela aceita e quanto vale, e salve.
        </p>
    <?php endif; ?>
<?php endif; ?>
