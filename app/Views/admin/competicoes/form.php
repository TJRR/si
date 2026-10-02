<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php $ehEdicao = $competicao !== null && isset($competicao['id']); ?>
<h1><?php echo $ehEdicao ? 'Editar competição' : 'Nova competição'; ?>: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<form method="post" action="<?php echo $ehEdicao ? url('competicoes/editar/' . (int) $evento['id'] . '/' . (int) $competicao['id']) : url('competicoes/novo/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
    <label>Nome da competição:
        <input type="text" name="nome" required maxlength="150" size="50" value="<?php echo htmlspecialchars((string) $dados['nome'], ENT_QUOTES, 'UTF-8'); ?>">
    </label>
    <p style="color:#555;font-size:0.9em;">É este nome que o participante vê no aplicativo, nas Regras do jogo e na mensagem de quando pontua.</p>

    <label>Atividade da programação em que acontece (opcional):
        <select name="atividade_id">
            <option value="0">Nenhuma</option>
            <?php foreach ($atividades as $atividade): ?>
                <option value="<?php echo (int) $atividade['id']; ?>" <?php echo (int) $dados['atividade_id'] === (int) $atividade['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($atividade['nome'], ENT_QUOTES, 'UTF-8'); ?>
                    (<?php echo htmlspecialchars(formatarDataHora($atividade['data_inicio']), ENT_QUOTES, 'UTF-8'); ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <p style="color:#555;font-size:0.9em;">
        Com atividade ligada, a leitura do código vale da abertura da leitura da atividade até o fim dela, e quem é
        facilitador dessa atividade vê o código em tela cheia em "Minhas facilitações" (e não pontua na competição).
        Sem atividade, a leitura vale nos dias do evento.
    </p>

    <label>Pontos por participação:
        <input type="number" name="pontos_participacao" min="0" max="65535" value="<?php echo (int) $dados['pontos_participacao']; ?>">
    </label>
    <p style="color:#555;font-size:0.9em;">
        Cada pessoa pontua uma vez por competição. O valor fica congelado no momento da leitura: mudar aqui vale só
        para as próximas participações.
    </p>

    <?php
    $nome = 'regras_html';
    $valor = (string) $dados['regras_html'];
    $rotulo = 'Regras da competição (opcional)';
    include __DIR__ . '/../_editor_rico.php';
    ?>
    <p style="color:#555;font-size:0.9em;">Aparecem para o participante na tela Regras do jogo.</p>

    <label>
        <input type="checkbox" name="ativo" value="1" <?php echo (int) $dados['ativo'] === 1 ? 'checked' : ''; ?>>
        Competição ativa
    </label>
    <p style="color:#555;font-size:0.9em;">Desativada, o código deixa de valer e a competição some das Regras do jogo. As participações já registradas continuam valendo.</p>

    <?php if ($ehEdicao): ?>
        <p>
            <strong>Código de participação:</strong>
            <span style="font-family:'Courier New',Courier,monospace;font-size:1.2em;letter-spacing:2px;"><?php echo htmlspecialchars($competicao['codigo_participacao'], ENT_QUOTES, 'UTF-8'); ?></span>
            (não muda, porque pode já estar impresso)
        </p>
    <?php endif; ?>

    <div class="form-acoes">
        <a href="<?php echo url('competicoes/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>
