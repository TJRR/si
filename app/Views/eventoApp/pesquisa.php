<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 57: pesquisa de satisfacao do participante.
 *
 * O aviso de anonimato e' TEXTO FIXO desta view, e nao campo configuravel:
 * a promessa precisa acompanhar o que o desenho das tabelas garante, e nao
 * pode ser apagada por engano numa tela de configuracao.
 *
 * Formulario comum, sem nenhum comportamento programado na tela, entao esta
 * view nao entra em nenhuma das listas de script de layout.php.
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
        <h2><?php echo htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8'); ?></h2>

        <?php if ($respondente !== null): ?>
            <div class="admin-card">
                <p>
                    <strong>Você já respondeu a esta pesquisa.</strong>
                    <br>
                    Resposta enviada em <?php echo htmlspecialchars(formatarDataHora($respondente['respondido_em']), ENT_QUOTES, 'UTF-8'); ?>.
                    <br>
                    <small>A pesquisa é respondida uma única vez, e as respostas não podem ser alteradas depois: elas são
                    guardadas sem ligação com o seu nome, então nem o sistema consegue encontrar as suas para trocar.</small>
                </p>
            </div>
        <?php elseif ((int) $config['ativo'] !== 1 || empty($perguntas)): ?>
            <p>A pesquisa de satisfação ainda não está disponível neste evento.</p>
        <?php elseif (!$dentroDaJanela): ?>
            <p>
                A pesquisa fica aberta<?php echo $janelaTexto !== '' ? ' de ' . htmlspecialchars($janelaTexto, ENT_QUOTES, 'UTF-8') : ''; ?>.
            </p>
        <?php else: ?>
            <div class="admin-card">
                <p>
                    <strong>As suas respostas são guardadas separadas do seu nome.</strong>
                    <br>
                    A organização vê que você respondeu, para creditar os pontos, e vê o conjunto das respostas,
                    mas não consegue ligar uma coisa à outra pelo sistema.
                </p>
            </div>

            <?php if (empty($pontua)): ?>
                <p style="color:#555;">
                    A sua resposta conta para o resultado da pesquisa, mas não credita pontos: os pontos
                    são dos participantes inscritos no evento.
                </p>
            <?php endif; ?>

            <?php if (!empty($config['texto_abertura_html'])): ?>
                <div class="admin-card"><?php echo sanitizarHtmlRico((string) $config['texto_abertura_html']); ?></div>
            <?php endif; ?>

            <form method="post" action="<?php echo url('eventoApp/pesquisaEnviar/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
                <?php foreach ($perguntas as $pergunta): ?>
                <?php
                $perguntaId = (int) $pergunta['id'];
                $configPergunta = $servico->configDa($pergunta);
                $opcoes = $servico->opcoesDa($pergunta);
                $valorAtual = isset($valores[$perguntaId]) ? $valores[$perguntaId] : null;
                ?>
                <fieldset>
                    <legend>
                        <?php echo htmlspecialchars($pergunta['enunciado'], ENT_QUOTES, 'UTF-8'); ?>
                        <?php if (!empty($pergunta['obrigatoria'])): ?><span aria-hidden="true">*</span><?php endif; ?>
                    </legend>

                    <?php if (!empty($pergunta['texto_ajuda'])): ?>
                        <p style="color:#555;font-size:0.9em;"><?php echo htmlspecialchars($pergunta['texto_ajuda'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>

                    <?php if ($pergunta['tipo'] === 'escala'): ?>
                        <?php
                        $rotuloMinimo = isset($configPergunta['rotulo_minimo']) ? (string) $configPergunta['rotulo_minimo'] : '';
                        $rotuloMaximo = isset($configPergunta['rotulo_maximo']) ? (string) $configPergunta['rotulo_maximo'] : '';
                        ?>
                        <?php if ($rotuloMinimo !== '' || $rotuloMaximo !== ''): ?>
                            <p style="color:#555;font-size:0.9em;">
                                <?php echo (int) $escalaMinima; ?> é <?php echo htmlspecialchars($rotuloMinimo, ENT_QUOTES, 'UTF-8'); ?>,
                                <?php echo (int) $escalaMaxima; ?> é <?php echo htmlspecialchars($rotuloMaximo, ENT_QUOTES, 'UTF-8'); ?>.
                            </p>
                        <?php endif; ?>
                        <?php for ($nota = (int) $escalaMinima; $nota <= (int) $escalaMaxima; $nota++): ?>
                            <label>
                                <input type="radio" name="resposta[<?php echo $perguntaId; ?>]" value="<?php echo $nota; ?>"
                                    <?php echo (string) $valorAtual === (string) $nota ? 'checked' : ''; ?>>
                                <?php echo $nota; ?>
                            </label>
                        <?php endfor; ?>

                    <?php elseif ($pergunta['tipo'] === 'lista_opcoes'): ?>
                        <?php foreach ($opcoes as $opcao): ?>
                            <label>
                                <input type="radio" name="resposta[<?php echo $perguntaId; ?>]"
                                    value="<?php echo htmlspecialchars($opcao, ENT_QUOTES, 'UTF-8'); ?>"
                                    <?php echo (string) $valorAtual === (string) $opcao ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars($opcao, ENT_QUOTES, 'UTF-8'); ?>
                            </label>
                        <?php endforeach; ?>

                    <?php elseif ($pergunta['tipo'] === 'multipla_escolha'): ?>
                        <?php $marcadas = is_array($valorAtual) ? $valorAtual : []; ?>
                        <?php foreach ($opcoes as $opcao): ?>
                            <label>
                                <input type="checkbox" name="resposta[<?php echo $perguntaId; ?>][]"
                                    value="<?php echo htmlspecialchars($opcao, ENT_QUOTES, 'UTF-8'); ?>"
                                    <?php echo in_array($opcao, $marcadas, true) ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars($opcao, ENT_QUOTES, 'UTF-8'); ?>
                            </label>
                        <?php endforeach; ?>

                    <?php else: ?>
                        <label>
                            <textarea name="resposta[<?php echo $perguntaId; ?>]" rows="4" maxlength="2000"><?php echo htmlspecialchars(is_scalar($valorAtual) ? (string) $valorAtual : '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </label>
                    <?php endif; ?>

                    <?php if (isset($erros[$perguntaId])): ?>
                        <p style="color:red;"><?php echo htmlspecialchars($erros[$perguntaId], ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                </fieldset>
                <?php endforeach; ?>

                <div class="form-acoes">
                    <button type="submit" class="app-btn-acao">Enviar respostas</button>
                </div>
            </form>

            <p style="color:#555;font-size:0.9em;">
                A pesquisa é respondida uma única vez, e as respostas não podem ser alteradas depois.
            </p>
        <?php endif; ?>
    </div>
</div>
