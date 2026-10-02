<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 59: seletor de plano de fundo do certificado. Inclusao:
 *   <?php $campoUrl = 'fundo_url'; $campoCor = 'fundo_cor';
 *         $valorUrl = $config['fundo_url']; $valorCor = $config['fundo_cor'];
 *         $rotuloFundo = 'Plano de fundo'; $eventoIdFundo = (int) $evento['id']; ?>
 *   <?php include __DIR__ . '/_seletor_fundo.php'; ?>
 *
 * Tres estados possiveis, e a tela diz em qual deles esta': uma imagem
 * escolhida na Biblioteca de mídia, uma cor, ou nada (folha branca). Imagem e
 * cor nunca valem juntas: escolher uma troca a outra, e o botao de limpar
 * tira as duas.
 *
 * Enquanto o Administrador nao escolher nada, o seletor mostra a arte que
 * acompanha o sistema e o campo oculto vai com ela - e' o padrao pedido pelo
 * dono do projeto, e nao um valor inventado pelo sistema: a arte existe no
 * repositorio e aparece na pasta "Fundo Certificados".
 *
 * A janela de escolha e o envio de arte nova sao tratados por
 * assets/js/seletor-fundo-certificado.js, que fala com certificados/fundos e
 * certificados/enviarFundo.
 */
$idSeletor = 'fundo-' . preg_replace('/[^a-z0-9]/', '', strtolower($campoUrl));
$urlAtual = (string) $valorUrl;
$corAtual = (string) $valorCor;
$urlPadrao = \App\Services\CertificadoFundoService::urlArtePadrao();
// $semPadraoFundo: usado onde o vazio tem significado proprio. No cadastro de
// uma atividade, nada escolhido quer dizer "herda a arte do evento", e nasce
// vazio; na configuracao do evento, nada escolhido mostra a arte que
// acompanha o sistema.
$comPadrao = empty($semPadraoFundo);
$urlEfetiva = $urlAtual !== '' ? $urlAtual : (($comPadrao && $corAtual === '') ? $urlPadrao : '');
$semImagem = $urlAtual === '' && $corAtual !== '';
$herda = !$comPadrao && $urlAtual === '' && $corAtual === '';
?>
<div class="seletor-fundo" data-seletor-fundo
     data-fundo-evento="<?php echo (int) $eventoIdFundo; ?>"
     data-fundo-id="<?php echo htmlspecialchars($idSeletor, ENT_QUOTES, 'UTF-8'); ?>">
    <p class="seletor-fundo-rotulo"><?php echo htmlspecialchars($rotuloFundo, ENT_QUOTES, 'UTF-8'); ?></p>

    <div class="seletor-fundo-corpo">
        <span class="seletor-fundo-amostra" data-fundo-amostra
              style="<?php echo $urlEfetiva !== ''
                  ? 'background-image:url(\'' . htmlspecialchars($urlEfetiva, ENT_QUOTES, 'UTF-8') . '\')'
                  : 'background-color:' . htmlspecialchars($corAtual !== '' ? $corAtual : '#ffffff', ENT_QUOTES, 'UTF-8'); ?>"></span>

        <div class="seletor-fundo-acoes">
            <button type="button" class="btn-acao" data-fundo-escolher>Escolher imagem</button>

            <button type="button" class="btn-icone" data-fundo-limpar title="Deixar o fundo sem imagem e sem cor" aria-label="Deixar o fundo sem imagem e sem cor">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
            </button>

            <label class="seletor-fundo-cor" title="Cor de fundo da folha">
                Cor
                <input type="color" data-fundo-cor value="<?php echo htmlspecialchars($corAtual !== '' ? $corAtual : '#ffffff', ENT_QUOTES, 'UTF-8'); ?>">
            </label>

            <span class="seletor-fundo-texto" data-fundo-texto>
                <?php if ($urlAtual !== ''): ?>
                    Imagem escolhida.
                <?php elseif ($semImagem): ?>
                    Fundo em cor, sem imagem.
                <?php elseif ($herda): ?>
                    Sem escolha: vale a arte do evento.
                <?php else: ?>
                    Arte que acompanha o sistema.
                <?php endif; ?>
            </span>
        </div>
    </div>

    <input type="hidden" name="<?php echo htmlspecialchars($campoUrl, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($urlEfetiva, ENT_QUOTES, 'UTF-8'); ?>" data-fundo-valor-url>
    <input type="hidden" name="<?php echo htmlspecialchars($campoCor, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($corAtual, ENT_QUOTES, 'UTF-8'); ?>" data-fundo-valor-cor>
</div>
<?php
/* As views do projeto compartilham um escopo só, então uma marca deixada por
   uma inclusão valeria para a seguinte. Cada inclusão precisa dizer o que
   quer, e por isso a marca é apagada aqui. */
unset($semPadraoFundo);
?>
