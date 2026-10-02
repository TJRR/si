<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Configurações de Conexões: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('conexoes/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<?php if (!$podeEditar): ?>
    <p class="status-pill laranja">Somente leitura: só o Administrador altera estas configurações.</p>
<?php endif; ?>

<form method="post" action="<?php echo url('conexoes/configuracoes/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
    <fieldset <?php echo $podeEditar ? '' : 'disabled'; ?>>
        <legend>Conexões entre participantes</legend>

        <label>
            <input type="checkbox" name="ativo" value="1" <?php echo (int) $config['ativo'] === 1 ? 'checked' : ''; ?>>
            Ativar as conexões neste evento
        </label>
        <p style="color:#555;font-size:0.9em;">
            Com as conexões desativadas, o aplicativo não oferece a leitura do código entre participantes e
            nenhuma conexão nova é registrada. As conexões já registradas continuam visíveis para quem as fez.
        </p>

        <label>Pontos por conexão
            <input type="number" name="pontos_por_conexao" min="0" max="65535" value="<?php echo (int) $config['pontos_por_conexao']; ?>">
        </label>
        <p style="color:#555;font-size:0.9em;">
            Quanto cada uma das duas pessoas ganha quando uma lê o código da outra, na tela do aplicativo ou no
            crachá. Com zero, a conexão é
            registrada e aparece na lista das duas, mas sem creditar ponto nenhum: serve para o evento que quer
            a lista de contatos sem a disputa de pontos. Para não registrar nada, desative as conexões acima.
        </p>

        <label>Limite de conexões que pontuam por participante
            <input type="number" name="teto_conexoes_pontuadas" min="0" max="65535" value="<?php echo (int) $config['teto_conexoes_pontuadas']; ?>">
        </label>
        <p style="color:#555;font-size:0.9em;">
            Zero significa sem limite. Passado o limite, a pessoa continua podendo se conectar e a conexão
            continua sendo registrada, só que sem pontos, e a tela avisa isso a ela.
        </p>

        <p style="color:#555;font-size:0.9em;">
            Mudar os pontos ou o limite vale só para as próximas conexões: o que já foi creditado não muda.
            As conexões só pontuam entre
            <?php echo htmlspecialchars(formatarData($evento['data_inicio']), ENT_QUOTES, 'UTF-8'); ?> e
            <?php echo htmlspecialchars(formatarData($evento['data_fim']), ENT_QUOTES, 'UTF-8'); ?>, as datas deste
            evento, e o último dia conta inteiro.
        </p>
    </fieldset>

    <?php if ($podeEditar): ?>
    <div class="form-acoes">
        <button type="submit">Salvar</button>
    </div>
    <?php endif; ?>
</form>
