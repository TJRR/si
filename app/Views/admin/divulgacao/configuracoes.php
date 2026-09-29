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
            Período em que as comprovações valem. Com os dois campos em branco valem as datas do evento
            (<?php echo htmlspecialchars(formatarData($evento['data_inicio']), ENT_QUOTES, 'UTF-8'); ?> a
            <?php echo htmlspecialchars(formatarData($evento['data_fim']), ENT_QUOTES, 'UTF-8'); ?>). Preencha
            para aceitar também a divulgação feita antes da abertura ou para dar prazo de envio depois do
            encerramento. O último dia conta inteiro.
        </p>
    </fieldset>

    <?php foreach ($redes as $chave => $rede): ?>
        <fieldset <?php echo $podeEditar ? '' : 'disabled'; ?>>
            <legend><?php echo htmlspecialchars($rede['rotulo'], ENT_QUOTES, 'UTF-8'); ?></legend>

            <label>
                <input type="checkbox" name="redes[<?php echo htmlspecialchars($chave, ENT_QUOTES, 'UTF-8'); ?>][publicacao_ativa]" value="1" <?php echo $rede['publicacao_ativa'] === 1 ? 'checked' : ''; ?>>
                Aceitar comprovação de publicação sobre o evento
            </label>

            <label>Pontos por publicação
                <input type="number" name="redes[<?php echo htmlspecialchars($chave, ENT_QUOTES, 'UTF-8'); ?>][publicacao_pontos]" min="0" max="65535" value="<?php echo (int) $rede['publicacao_pontos']; ?>">
            </label>

            <label>O que a pessoa envia como prova da publicação
                <select name="redes[<?php echo htmlspecialchars($chave, ENT_QUOTES, 'UTF-8'); ?>][publicacao_prova]">
                    <option value="ambos"<?php echo $rede['publicacao_prova'] === 'ambos' ? ' selected' : ''; ?>>Imagem da tela ou endereço da publicação</option>
                    <option value="imagem"<?php echo $rede['publicacao_prova'] === 'imagem' ? ' selected' : ''; ?>>Só a imagem da tela</option>
                    <option value="endereco"<?php echo $rede['publicacao_prova'] === 'endereco' ? ' selected' : ''; ?>>Só o endereço da publicação</option>
                </select>
            </label>

            <label>Limite de publicações que pontuam por dia
                <input type="number" name="redes[<?php echo htmlspecialchars($chave, ENT_QUOTES, 'UTF-8'); ?>][publicacao_teto_dia]" min="0" max="65535" value="<?php echo (int) $rede['publicacao_teto_dia']; ?>">
            </label>

            <label>Limite de publicações que pontuam no evento inteiro
                <input type="number" name="redes[<?php echo htmlspecialchars($chave, ENT_QUOTES, 'UTF-8'); ?>][publicacao_teto_evento]" min="0" max="65535" value="<?php echo (int) $rede['publicacao_teto_evento']; ?>">
            </label>

            <p style="color:#555;font-size:0.9em;">
                Zero em um limite significa sem limite naquela contagem; os dois preenchidos valem juntos.
                Passado o limite, a comprovação continua sendo registrada, só que sem pontos, e a tela avisa
                isso à pessoa.
            </p>

            <label>
                <input type="checkbox" name="redes[<?php echo htmlspecialchars($chave, ENT_QUOTES, 'UTF-8'); ?>][acompanhar_ativa]" value="1" <?php echo $rede['acompanhar_ativa'] === 1 ? 'checked' : ''; ?>>
                Aceitar comprovação de que passou a acompanhar este canal do Tribunal
            </label>

            <label>Pontos por passar a acompanhar
                <input type="number" name="redes[<?php echo htmlspecialchars($chave, ENT_QUOTES, 'UTF-8'); ?>][acompanhar_pontos]" min="0" max="65535" value="<?php echo (int) $rede['acompanhar_pontos']; ?>">
            </label>

            <label>O que a pessoa envia como prova de que acompanha
                <select name="redes[<?php echo htmlspecialchars($chave, ENT_QUOTES, 'UTF-8'); ?>][acompanhar_prova]">
                    <option value="imagem"<?php echo $rede['acompanhar_prova'] === 'imagem' ? ' selected' : ''; ?>>Só a imagem da tela</option>
                    <option value="ambos"<?php echo $rede['acompanhar_prova'] === 'ambos' ? ' selected' : ''; ?>>Imagem da tela ou endereço</option>
                    <option value="endereco"<?php echo $rede['acompanhar_prova'] === 'endereco' ? ' selected' : ''; ?>>Só o endereço</option>
                </select>
            </label>

            <p style="color:#555;font-size:0.9em;">
                Passar a acompanhar pontua uma única vez por rede. Quem acompanha três redes soma os pontos das
                três.
            </p>
        </fieldset>
    <?php endforeach; ?>

    <p style="color:#555;font-size:0.9em;">
        Mudar pontos, limites ou período vale só para as próximas comprovações: o que já foi creditado não muda.
        Deixar uma rede ativa com pontos zerados é combinação válida, e registra a comprovação sem creditar
        ponto nenhum; para não registrar nada, deixe a rede desativada.
    </p>

    <?php if ($podeEditar): ?>
    <div class="form-acoes">
        <button type="submit">Salvar</button>
    </div>
    <?php endif; ?>
</form>

<?php if ($podeEditar): ?>
<div class="admin-card">
    <h2>Apagar as imagens de comprovação</h2>
    <p style="color:#555;font-size:0.9em;">
        As imagens de tela enviadas pelos participantes ficam guardadas em área restrita e podem conter dados de
        outras pessoas. Depois do evento, apague-as. As comprovações, os pontos e a proteção contra reenvio da
        mesma imagem continuam funcionando: só o arquivo é apagado, e a ação não pode ser desfeita.
    </p>
    <p><strong><?php echo (int) $totalImagens; ?></strong> <?php echo (int) $totalImagens === 1 ? 'imagem guardada' : 'imagens guardadas'; ?></p>

    <?php if (!$eventoEncerrado): ?>
        <p class="status-pill laranja">Disponível depois que o evento passar da própria data final.</p>
    <?php elseif ((int) $totalImagens > 0): ?>
        <form method="post" action="<?php echo url('divulgacao/expurgarImagens/' . (int) $evento['id']); ?>" onsubmit="return confirm('Apagar definitivamente todas as imagens de comprovação deste evento?');"><?= campoCsrf() ?>
            <label>Digite o nome do evento para confirmar
                <input type="text" name="confirmacao" required placeholder="<?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?>">
            </label>
            <button type="submit">Apagar as imagens</button>
        </form>
    <?php endif; ?>
</div>
<?php endif; ?>
