<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
$encerramentoAgendado = !empty($config['encerramento_em']) && empty($gincanaEncerrada);
?>
<div class="pagina-titulo-acoes">
    <h1>Configurações da gamificação: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('gamificacao/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<?php if (!$podeEditar): ?>
    <p class="status-pill laranja">Somente leitura: só o Administrador altera estas configurações.</p>
<?php endif; ?>

<form method="post" action="<?php echo url('gamificacao/configuracoes/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
    <fieldset <?php echo $podeEditar ? '' : 'disabled'; ?>>
        <legend>No aplicativo</legend>

        <label>
            <input type="checkbox" name="ativo" value="1" <?php echo (int) $config['ativo'] === 1 ? 'checked' : ''; ?>>
            Mostrar a pontuação no aplicativo ("Minha pontuação", "Regras do jogo" e o total no painel)
        </label>
        <p style="color:#555;font-size:0.9em;">
            Desligado, o participante não vê o total nem a classificação. Os pontos continuam sendo calculados e
            creditados pelos módulos de cada origem.
        </p>

        <label>
            <input type="checkbox" name="classificacao_visivel" value="1" <?php echo (int) $config['classificacao_visivel'] === 1 ? 'checked' : ''; ?>>
            Mostrar aos inscritos os primeiros da classificação
        </label>

        <label>Quantos primeiros aparecem
            <input type="number" name="classificacao_quantidade" min="1" max="10000" value="<?php echo (int) $config['classificacao_quantidade'] > 0 ? (int) $config['classificacao_quantidade'] : ''; ?>">
        </label>

        <label>
            <input type="checkbox" name="classificacao_mostrar_nomes" value="1" <?php echo (int) $config['classificacao_mostrar_nomes'] === 1 ? 'checked' : ''; ?>>
            Mostrar o nome de quem está entre os primeiros
        </label>
        <p style="color:#555;font-size:0.9em;">
            Cada inscrito sempre vê a própria posição e o próprio total. Sem os nomes, a lista dos primeiros mostra só a
            posição e os pontos, e a linha da própria pessoa aparece destacada. O nome de quem está abaixo dos
            primeiros nunca aparece para os outros inscritos.
        </p>
    </fieldset>

    <fieldset <?php echo $podeEditar ? '' : 'disabled'; ?>>
        <legend>Extra de pontualidade</legend>

        <label>Minutos de antecedência
            <input type="number" name="minutos_pontualidade" min="0" max="240" value="<?php echo $config['minutos_pontualidade'] !== null ? (int) $config['minutos_pontualidade'] : ''; ?>">
        </label>
        <p style="color:#555;font-size:0.9em;">
            Quem confirma presença até este número de minutos antes do início da atividade recebe o extra de
            pontualidade do tipo de atividade (ou da própria atividade). Em branco, o extra não vale para ninguém. A
            leitura só é aceita a partir da abertura definida em cada atividade (15, 30 ou 60 minutos antes): a
            antecedência daqui precisa ser menor que a abertura para o extra ser alcançável.
        </p>
    </fieldset>

    <fieldset <?php echo $podeEditar ? '' : 'disabled'; ?>>
        <legend>Regras do jogo</legend>
        <?php
        $nome = 'texto_regras_html';
        $valor = (string) $config['texto_regras_html'];
        $rotulo = 'Texto de abertura (opcional)';
        include __DIR__ . '/../_editor_rico.php';
        ?>
        <p style="color:#555;font-size:0.9em;">
            Aparece no topo da tela "Regras do jogo" do aplicativo. O restante da tela é montado sozinho a partir do
            cadastro de cada módulo (pontos por tipo de atividade, bônus, estandes, conexões, redes, competições,
            credenciamento e desempate), então nunca fica diferente do que o sistema de fato credita.
        </p>
    </fieldset>

    <?php if ($podeEditar): ?>
    <div class="form-acoes">
        <button type="submit">Salvar</button>
    </div>
    <?php endif; ?>
</form>

<div class="admin-card">
    <h2>Encerramento da gincana</h2>
    <p style="color:#555;font-size:0.9em;">
        Depois do encerramento nada mais pontua e a classificação fica congelada: presenças, visitas a estandes,
        conexões e credenciamentos continuam registrados, sem pontos; divulgação e participação em competições são
        recusadas; nenhuma anulação ou reversão move pontos. Enquanto o instante agendado não chega, ele pode ser
        alterado ou retirado. Quando chega, o encerramento é definitivo.
    </p>

    <?php if (!empty($gincanaEncerrada)): ?>
        <p class="status-pill vermelho">Gincana encerrada em <?php echo htmlspecialchars(formatarDataHora($config['encerramento_em']), ENT_QUOTES, 'UTF-8'); ?>. O encerramento é definitivo.</p>
    <?php else: ?>
        <?php if ($encerramentoAgendado): ?>
            <p class="status-pill laranja">Encerramento agendado para <?php echo htmlspecialchars(formatarDataHora($config['encerramento_em']), ENT_QUOTES, 'UTF-8'); ?>.</p>
        <?php else: ?>
            <p>Nenhum encerramento agendado.</p>
        <?php endif; ?>

        <?php if ($podeEditar): ?>
            <form method="post" action="<?php echo url('gamificacao/encerramento/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
                <input type="hidden" name="acao" value="agendar">
                <label>Agendar para
                    <input type="datetime-local" name="encerramento_em" required value="<?php echo $encerramentoAgendado ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($config['encerramento_em'])), ENT_QUOTES, 'UTF-8') : ''; ?>">
                </label>
                <button type="submit"><?php echo $encerramentoAgendado ? 'Alterar o agendamento' : 'Agendar o encerramento'; ?></button>
            </form>

            <?php if ($encerramentoAgendado): ?>
                <form method="post" action="<?php echo url('gamificacao/encerramento/' . (int) $evento['id']); ?>" onsubmit="return confirm('Retirar o encerramento agendado?');"><?= campoCsrf() ?>
                    <input type="hidden" name="acao" value="retirar">
                    <button type="submit">Retirar o agendamento</button>
                </form>
            <?php endif; ?>

            <form method="post" action="<?php echo url('gamificacao/encerramento/' . (int) $evento['id']); ?>" onsubmit="return confirm('Encerrar a gincana agora? Não há volta: nada mais pontua e a classificação fica congelada.');"><?= campoCsrf() ?>
                <input type="hidden" name="acao" value="agora">
                <label>Digite o nome do evento para encerrar agora
                    <input type="text" name="confirmacao" required placeholder="<?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?>">
                </label>
                <button type="submit">Encerrar a gincana agora</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>
