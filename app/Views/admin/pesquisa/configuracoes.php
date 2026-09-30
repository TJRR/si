<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Configurações da pesquisa: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('pesquisa/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<?php if (!$podeEditar): ?>
    <p class="status-pill laranja">Somente leitura: só o Administrador altera estas configurações.</p>
<?php endif; ?>

<?php if ($totalPerguntasAtivas === 0): ?>
    <p class="status-pill laranja">
        Nenhuma pergunta ativa: a pesquisa não abre para o participante enquanto não houver ao menos uma.
        Cadastre em <a href="<?php echo url('pesquisa/perguntas/' . (int) $evento['id']); ?>">Perguntas</a>.
    </p>
<?php endif; ?>

<?php if (!$temBonusDaPesquisa): ?>
    <p class="status-pill laranja">
        Responder não está creditando pontos: não há bônus ativo do tipo "responder à pesquisa de satisfação".
        Cadastre um em <a href="<?php echo url('bonus/index/' . (int) $evento['id']); ?>">Bônus</a>.
    </p>
<?php endif; ?>

<form method="post" action="<?php echo url('pesquisa/configuracoes/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
    <fieldset <?php echo $podeEditar ? '' : 'disabled'; ?>>
        <legend>Pesquisa de satisfação</legend>

        <label>
            <input type="checkbox" name="ativo" value="1" <?php echo (int) $config['ativo'] === 1 ? 'checked' : ''; ?>>
            Ativar a pesquisa neste evento
        </label>
        <p style="color:#555;font-size:0.9em;">
            Com a pesquisa desativada, o botão some do aplicativo e nenhuma resposta nova é aceita.
            As respostas já enviadas continuam no resultado.
        </p>

        <label>Título:
            <input type="text" name="titulo" maxlength="150" size="50" value="<?php echo htmlspecialchars((string) $config['titulo'], ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <p style="color:#555;font-size:0.9em;">Em branco, a tela usa "<?php echo htmlspecialchars($tituloVigente, ENT_QUOTES, 'UTF-8'); ?>".</p>

        <label>Abre em:
            <input type="date" name="data_inicio" value="<?php echo htmlspecialchars((string) $config['data_inicio'], ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <label>Fecha em:
            <input type="date" name="data_fim" value="<?php echo htmlspecialchars((string) $config['data_fim'], ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <p style="color:#555;font-size:0.9em;">
            As duas datas em branco fazem valer as datas do evento, de
            <?php echo htmlspecialchars(formatarData($evento['data_inicio']), ENT_QUOTES, 'UTF-8'); ?> a
            <?php echo htmlspecialchars(formatarData($evento['data_fim']), ENT_QUOTES, 'UTF-8'); ?>.
            A comparação é por data, então o último dia conta inteiro.
        </p>

        <?php
        $nome = 'texto_abertura_html';
        $valor = (string) $config['texto_abertura_html'];
        $rotulo = 'Texto de abertura (o participante lê antes de responder)';
        include __DIR__ . '/../_editor_rico.php';
        ?>
        <p style="color:#555;font-size:0.9em;">
            O aviso de que as respostas são guardadas separadas do nome já aparece sempre na tela,
            e não depende deste texto.
        </p>

        <label>Assunto do convite:
            <input type="text" name="convite_assunto" maxlength="200" size="60" value="<?php echo htmlspecialchars((string) $config['convite_assunto'], ENT_QUOTES, 'UTF-8'); ?>">
        </label>

        <?php
        $nome = 'convite_corpo_html';
        $valor = (string) $config['convite_corpo_html'];
        $rotulo = 'Corpo do convite';
        include __DIR__ . '/../_editor_rico.php';
        ?>
        <p style="color:#555;font-size:0.9em;">
            É a mensagem por correio eletrônico disparada pelo botão "Convidar a responder", no Resultado.
            Em branco, o sistema envia um texto padrão.
        </p>
    </fieldset>

    <?php if ($podeEditar): ?>
    <div class="form-acoes">
        <button type="submit">Salvar</button>
    </div>
    <?php endif; ?>
</form>
