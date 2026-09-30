<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
$ehEdicao = $pergunta !== null && isset($pergunta['id']);
$config = isset($dados['config']) && is_array($dados['config']) ? $dados['config'] : [];
$opcoesTexto = isset($config['opcoes']) && is_array($config['opcoes']) ? implode("\n", $config['opcoes']) : '';
$rotuloMinimo = isset($config['rotulo_minimo']) ? (string) $config['rotulo_minimo'] : '';
$rotuloMaximo = isset($config['rotulo_maximo']) ? (string) $config['rotulo_maximo'] : '';
?>
<h1><?php echo $ehEdicao ? 'Editar pergunta' : 'Nova pergunta'; ?>: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if ($congelada): ?>
    <p class="status-pill laranja">
        Esta pergunta já tem respostas: o enunciado, o tipo e as opções não podem mais mudar, porque as
        respostas guardam a posição da opção escolhida. O texto de ajuda, a obrigatoriedade e a situação
        continuam editáveis.
    </p>
<?php endif; ?>

<form method="post" action="<?php echo $ehEdicao ? url('pesquisa/editarPergunta/' . (int) $evento['id'] . '/' . (int) $pergunta['id']) : url('pesquisa/novaPergunta/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
    <label>Pergunta:
        <input type="text" name="enunciado" required maxlength="300" size="70" value="<?php echo htmlspecialchars((string) $dados['enunciado'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $congelada ? 'readonly' : ''; ?>>
    </label>
    <?php if (isset($erros['enunciado'])): ?><p style="color:red;"><?php echo htmlspecialchars($erros['enunciado'], ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>

    <label>Tipo de resposta:
        <select name="tipo" id="pergunta-tipo" <?php echo $congelada ? 'disabled' : ''; ?>>
            <?php foreach ($tipos as $valor => $rotulo): ?>
                <option value="<?php echo htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $dados['tipo'] === $valor ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <div id="pergunta-opcoes">
        <label>Opções, uma por linha:<br>
            <textarea name="opcoes" rows="5" cols="50" <?php echo $congelada ? 'readonly' : ''; ?>><?php echo htmlspecialchars($opcoesTexto, ENT_QUOTES, 'UTF-8'); ?></textarea>
        </label>
        <?php if (isset($erros['opcoes'])): ?><p style="color:red;"><?php echo htmlspecialchars($erros['opcoes'], ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
        <p style="color:#555;font-size:0.9em;">Ao menos duas opções. A ordem desta lista é a ordem em que elas aparecem para quem responde.</p>
    </div>

    <div id="pergunta-escala">
        <label>Nome do extremo mais baixo (nota <?php echo (int) $escalaMinima; ?>):
            <input type="text" name="rotulo_minimo" maxlength="60" value="<?php echo htmlspecialchars($rotuloMinimo, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $congelada ? 'readonly' : ''; ?>>
        </label>
        <label>Nome do extremo mais alto (nota <?php echo (int) $escalaMaxima; ?>):
            <input type="text" name="rotulo_maximo" maxlength="60" value="<?php echo htmlspecialchars($rotuloMaximo, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $congelada ? 'readonly' : ''; ?>>
        </label>
        <p style="color:#555;font-size:0.9em;">
            Por exemplo, "Muito ruim" e "Muito bom". Deixando em branco, aparecem só os números de
            <?php echo (int) $escalaMinima; ?> a <?php echo (int) $escalaMaxima; ?>.
        </p>
    </div>

    <label>
        <input type="checkbox" name="obrigatoria" value="1" <?php echo (int) $dados['obrigatoria'] === 1 ? 'checked' : ''; ?>>
        Obrigatória
    </label>

    <label>Texto de ajuda (aparece abaixo da pergunta):
        <input type="text" name="texto_ajuda" maxlength="255" size="60" value="<?php echo htmlspecialchars((string) $dados['texto_ajuda'], ENT_QUOTES, 'UTF-8'); ?>">
    </label>

    <label>
        <input type="checkbox" name="ativa" value="1" <?php echo (int) $dados['ativa'] === 1 ? 'checked' : ''; ?>>
        Pergunta ativa
    </label>
    <p style="color:#555;font-size:0.9em;">Pergunta desativada sai do formulário e continua no resultado.</p>

    <div class="form-acoes">
        <a href="<?php echo url('pesquisa/perguntas/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>

<script>
(function () {
    var selectTipo = document.getElementById('pergunta-tipo');
    var blocoOpcoes = document.getElementById('pergunta-opcoes');
    var blocoEscala = document.getElementById('pergunta-escala');

    function ajustar() {
        var tipo = selectTipo.value;
        blocoOpcoes.style.display = (tipo === 'lista_opcoes' || tipo === 'multipla_escolha') ? 'block' : 'none';
        blocoEscala.style.display = tipo === 'escala' ? 'block' : 'none';
    }

    selectTipo.addEventListener('change', ajustar);
    ajustar();
})();
</script>
