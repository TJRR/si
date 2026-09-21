<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
    // Fase 35: a semente da pergunta e' cortada em LIMITE_PERGUNTA porque
    // perguntas_frequentes.pergunta e' VARCHAR(255) e duvidas.pergunta e'
    // TEXT. So' avisa quando o corte realmente aconteceu.
    $perguntaOriginal = trim($duvida['pergunta']);
    $perguntaFoiCortada = mb_strlen($perguntaOriginal) > $limitePergunta;

    // A mais recente e' a ULTIMA (listarPorDuvida vem ORDER BY criado_em ASC)
    // e ja' esta no campo; aqui ficam so' as anteriores, pra copiar trecho.
    $respostasAnteriores = array_slice($respostas, 0, -1);
?>
<div class="pagina-titulo-acoes">
    <h1>Transformar em pergunta frequente</h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('duvidaAdmin/ver/' . (int) $duvida['id']); ?>" class="btn-voltar">Voltar à dúvida</a>
    </div>
</div>

<p>Origem: dúvida #<?php echo (int) $duvida['id']; ?> (
    <?php echo htmlspecialchars($duvida['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?> ·
    <?php echo htmlspecialchars($duvida['trilha_nome'], ENT_QUOTES, 'UTF-8'); ?> ·
    <?php echo htmlspecialchars($duvida['concurso_nome'], ENT_QUOTES, 'UTF-8'); ?>).
    A dúvida original e a resposta enviada à equipe <strong>não mudam</strong>: o que se cria aqui é uma pergunta frequente nova e independente.</p>

<?php if (!empty($erro)): ?>
    <p class="flash-mensagem erro"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php if (!empty($faqsGerados)): ?>
    <div class="promover-faq-aviso alerta">
        <strong>Esta dúvida já gerou pergunta frequente.</strong>
        Você pode criar outra (uma dúvida longa pode render mais de uma pergunta), mas confira antes se não vai duplicar:
        <ul>
            <?php foreach ($faqsGerados as $gerado): ?>
                <li><a href="<?php echo url('faq/editar/' . (int) $gerado['id']); ?>"><?php echo htmlspecialchars($gerado['pergunta'], ENT_QUOTES, 'UTF-8'); ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="promover-faq-aviso atencao">
    <strong>Este texto vai para uma página pública.</strong>
    A dúvida foi escrita por um participante e pode conter nome da equipe, nome do projeto, dado pessoal e detalhe de submissão sob sigilo.
    Reescreva a pergunta e a resposta em termos genéricos antes de salvar: o preenchimento abaixo é só um ponto de partida.
    Anexos da dúvida e das respostas <strong>não</strong> acompanham a pergunta frequente.
</div>

<form method="post" action="<?php echo url('duvidaAdmin/promoverFaq/' . (int) $duvida['id']); ?>"><?= campoCsrf() ?>
    <label>Categoria (ex.: Inscrição, Avaliação, Premiação):
        <input type="text" name="categoria" value="<?php echo htmlspecialchars($entrada['categoria'], ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Pergunta:
        <input type="text" name="pergunta" required maxlength="<?php echo (int) $limitePergunta; ?>" value="<?php echo htmlspecialchars($entrada['pergunta'], ENT_QUOTES, 'UTF-8'); ?>">
    </label>
    <p class="promover-faq-dica">
        Máximo de <?php echo (int) $limitePergunta; ?> caracteres.
        <?php if ($perguntaFoiCortada): ?>
            <strong>O texto da dúvida era mais longo que isso e foi cortado no preenchimento</strong>: reescreva como uma pergunta curta e genérica.
        <?php endif; ?>
    </p>

    <label>Resposta:<br>
        <textarea name="resposta" rows="8" cols="60" required><?php echo htmlspecialchars($entrada['resposta'], ENT_QUOTES, 'UTF-8'); ?></textarea>
    </label>
    <p class="promover-faq-dica">Preenchida com a resposta mais recente da dúvida.</p>

    <fieldset class="promover-faq-destino">
        <legend>Destino</legend>
        <p class="promover-faq-dica">Toda pergunta nasce obrigatoriamente no banco geral, que é acumulativo entre edições. Por isso existem só estas duas opções: não há uma terceira.</p>

        <label>
            <input type="radio" name="destino" value="banco" <?php echo $entrada['ativar'] ? '' : 'checked'; ?>>
            Só no banco geral: fica guardada, mas <strong>não aparece</strong> em nenhuma home até ser ativada.
        </label><br>

        <label>
            <input type="radio" name="destino" value="ativar" <?php echo $entrada['ativar'] ? 'checked' : ''; ?>>
            Banco geral <strong>e</strong> ativa na edição escolhida: passa a aparecer na home dessa edição.
        </label><br>

        <label>Edição:
            <select name="concurso_id">
                <?php foreach ($concursos as $concurso): ?>
                    <option value="<?php echo (int) $concurso['id']; ?>" <?php echo (int) $entrada['concurso_id'] === (int) $concurso['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <p class="promover-faq-dica">Sugerida a edição da própria dúvida. Só usada quando a opção acima está marcada.</p>
    </fieldset>

    <div class="form-acoes">
        <a href="<?php echo url('duvidaAdmin/ver/' . (int) $duvida['id']); ?>" class="btn-voltar">Cancelar</a>
        <button type="submit">Criar pergunta frequente</button>
    </div>
</form>

<?php if (!empty($respostasAnteriores)): ?>
    <h2>Respostas anteriores desta dúvida</h2>
    <p class="promover-faq-dica">Somente leitura: a dúvida foi reaberta e acumulou mais de uma resposta. Copie daqui o que quiser aproveitar.</p>
    <?php foreach ($respostasAnteriores as $anterior): ?>
        <div class="promover-faq-anterior">
            <p class="promover-faq-dica">
                <?php echo htmlspecialchars($anterior['usuario_nome'], ENT_QUOTES, 'UTF-8'); ?> ·
                <?php echo htmlspecialchars(formatarDataHora($anterior['criado_em']), ENT_QUOTES, 'UTF-8'); ?>
            </p>
            <p><?php echo nl2br(htmlspecialchars($anterior['resposta'], ENT_QUOTES, 'UTF-8')); ?></p>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
