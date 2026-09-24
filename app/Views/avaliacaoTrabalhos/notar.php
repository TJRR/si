<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>

<div class="site-page">
    <?php
    $eventoId = $trabalho['evento_id'];
    $tituloTopo = $trabalho['evento_nome'];
    $urlVoltar = url('avaliacaoTrabalhos/index');
    require __DIR__ . '/../eventoApp/_app_bar.php';
    ?>

    <div class="site-form-page">
        <?php require __DIR__ . '/../eventoApp/_ajuda_card.php'; ?>
        <h2>
            <?php if ($sigiloCego): ?>
                Trabalho nº <?php echo (int) $trabalho['numero_sigilo']; ?>
            <?php else: ?>
                <?php echo htmlspecialchars((string) $trabalho['autor_principal_nome'], ENT_QUOTES, 'UTF-8'); ?>
            <?php endif; ?>
        </h2>

        <div class="admin-card">
            <p><strong><?php echo htmlspecialchars($trabalho['titulo'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
            <p><strong>Eixo temático:</strong> <?php echo htmlspecialchars((string) $trabalho['eixo_nome'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>Natureza:</strong> <?php echo htmlspecialchars((string) $trabalho['natureza_nome'], ENT_QUOTES, 'UTF-8'); ?></p>

            <h3>Conteúdo</h3>
            <?php if ($trabalho['metodo_submissao'] === 'formulario'): ?>
                <div><?php echo $trabalho['conteudo_html']; ?></div>
            <?php elseif ($trabalho['metodo_submissao'] === 'link_externo'): ?>
                <p><a href="<?php echo htmlspecialchars($trabalho['link_avaliacao'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Abrir documento</a></p>
            <?php else: ?>
                <?php
                $extensaoArquivo = !empty($trabalho['arquivo_avaliacao_path']) ? strtoupper(pathinfo($trabalho['arquivo_avaliacao_path'], PATHINFO_EXTENSION)) : null;
                $dicaDownload = $extensaoArquivo !== null ? $extensaoArquivo . ' · trabalho submetido' : 'Trabalho submetido';
                ?>
                <div class="avaliacao-download-linha">
                    <a href="<?php echo url('avaliacaoTrabalhos/arquivo/' . (int) $trabalho['id']); ?>" class="btn btn-solido avaliacao-download-botao" title="<?php echo htmlspecialchars($dicaDownload, ENT_QUOTES, 'UTF-8'); ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        Baixar documento
                    </a>
                    <button type="button" class="avaliacao-edital-botao" data-abrir-painel="painel-criterios-edital" aria-label="Consultar critérios de avaliação do edital">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                        </svg>
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <div class="admin-card">
            <h3>Notas</h3>
            <form method="post" action="<?php echo url('avaliacaoTrabalhos/notar/' . (int) $trabalho['id']); ?>"><?= campoCsrf() ?>
                <?php foreach ($criterios as $criterio): ?>
                    <?php
                    $notaMaxima = (float) $criterio['nota_maxima'];
                    $valorAtual = isset($notasLancadas[(int) $criterio['id']]) ? (float) $notasLancadas[(int) $criterio['id']] : 0;
                    $idCampo = 'nota_' . (int) $criterio['id'];
                    $idAjuda = 'ajuda_' . (int) $criterio['id'];
                    ?>
                    <div class="avaliacao-criterio" data-max="<?php echo htmlspecialchars((string) $notaMaxima, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="avaliacao-criterio-cabecalho">
                            <label><?php echo htmlspecialchars($criterio['nome'], ENT_QUOTES, 'UTF-8'); ?></label>
                            <?php if (!empty($criterio['descricao'])): ?>
                                <button type="button" class="avaliacao-ajuda-botao" data-alvo="<?php echo $idAjuda; ?>" aria-label="O que este critério avalia">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                    </svg>
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($criterio['descricao'])): ?>
                            <p class="avaliacao-ajuda-texto" id="<?php echo $idAjuda; ?>" hidden><?php echo htmlspecialchars($criterio['descricao'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>

                        <!-- Achado do usuário: um controle só - a caixa
                        central mostra/recebe a nota (digitável, aceita
                        vírgula), a régua abaixo é outro jeito de ajustar o
                        MESMO valor, sempre em sincronia. -->
                        <div class="avaliacao-criterio-controle">
                            <button type="button" class="avaliacao-numero-botao" data-passo="-0.1" aria-label="Diminuir 0,1">−</button>
                            <input type="text" inputmode="decimal" class="avaliacao-criterio-numero"
                                   aria-label="Nota de <?php echo htmlspecialchars($criterio['nome'], ENT_QUOTES, 'UTF-8'); ?>"
                                   value="<?php echo number_format($valorAtual, 2, ',', ''); ?>">
                            <button type="button" class="avaliacao-numero-botao" data-passo="0.1" aria-label="Aumentar 0,1">+</button>
                            <span class="avaliacao-criterio-max">/ <?php echo number_format($notaMaxima, 2, ',', ''); ?></span>
                        </div>
                        <input type="range" class="avaliacao-criterio-range"
                               name="<?php echo $idCampo; ?>"
                               aria-label="Ajustar nota de <?php echo htmlspecialchars($criterio['nome'], ENT_QUOTES, 'UTF-8'); ?> com a régua"
                               min="0" max="<?php echo htmlspecialchars((string) $notaMaxima, ENT_QUOTES, 'UTF-8'); ?>" step="0.01"
                               value="<?php echo htmlspecialchars((string) $valorAtual, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                <?php endforeach; ?>
                <button type="submit" class="btn">Salvar notas</button>
            </form>
        </div>
    </div>
</div>

<!-- Fase 49B, achado do usuário: painel de consulta aos critérios do
     edital (nunca o edital inteiro), reaproveitando o mesmo painel
     deslizante genérico do menu do app (assets/js/painel-lateral.js já
     abre/fecha por id e reaproveita o backdrop que _menu_painel.php já
     injeta nesta página) - só como variante "fundo" (desliza de baixo
     para cima, ver .site-painel-fundo em site.css) em vez de lateral. -->
<aside id="painel-criterios-edital" class="site-painel-lateral site-painel-fundo" aria-hidden="true">
    <div class="site-painel-cabecalho">
        <h2>Critérios de avaliação</h2>
        <button type="button" class="site-painel-fechar" data-fechar-painel aria-label="Fechar">&times;</button>
    </div>
    <div class="site-painel-corpo">
        <?php if ($criteriosResumoHtml !== null): ?>
            <?php echo $criteriosResumoHtml; ?>
        <?php else: ?>
            <p>A organização ainda não cadastrou o resumo dos critérios de avaliação deste edital.</p>
        <?php endif; ?>
    </div>
</aside>

<script>
(function () {
    'use strict';

    // Ajuda por critério: cada botão alterna o texto associado
    // (data-alvo), discreto - só aparece quando a pessoa pede.
    document.querySelectorAll('.avaliacao-ajuda-botao').forEach(function (botao) {
        botao.addEventListener('click', function () {
            var alvo = document.getElementById(botao.dataset.alvo);

            if (alvo) {
                alvo.hidden = !alvo.hidden;
            }
        });
    });

    // Caixa numérica e régua são duas formas de ajustar o MESMO valor -
    // só a régua tem "name" (é o campo que de fato é enviado no POST); a
    // caixa numérica e os botões -/+ são um reforço por cima, sempre
    // sincronizados com ela. Aceita vírgula como separador decimal.
    document.querySelectorAll('.avaliacao-criterio').forEach(function (bloco) {
        var max = parseFloat(bloco.dataset.max);
        var campoNumero = bloco.querySelector('.avaliacao-criterio-numero');
        var campoRange = bloco.querySelector('.avaliacao-criterio-range');

        function limitar(valor) {
            if (isNaN(valor)) { valor = 0; }
            valor = Math.max(0, Math.min(max, valor));
            return Math.round(valor * 100) / 100;
        }

        // Usado ao terminar a edição (sair do campo, mexer na régua, usar
        // os botões -/+) - reformata a caixa numérica por completo.
        function definirValorCompleto(valor) {
            valor = limitar(valor);
            campoNumero.value = valor.toFixed(2).replace('.', ',');
            campoRange.value = valor;
        }

        // Usado enquanto a pessoa ainda está digitando - só atualiza a
        // régua (reformatar a caixa a cada tecla atrapalharia digitar
        // "1,5", por exemplo), nunca mexe no que já foi digitado.
        function acompanharDigitacao(valorBruto) {
            if (!isNaN(valorBruto)) {
                campoRange.value = limitar(valorBruto);
            }
        }

        campoRange.addEventListener('input', function () {
            definirValorCompleto(parseFloat(campoRange.value));
        });

        campoNumero.addEventListener('input', function () {
            acompanharDigitacao(parseFloat(campoNumero.value.replace(',', '.')));
        });

        campoNumero.addEventListener('blur', function () {
            definirValorCompleto(parseFloat(campoNumero.value.replace(',', '.')));
        });

        bloco.querySelectorAll('.avaliacao-numero-botao').forEach(function (botaoPasso) {
            botaoPasso.addEventListener('click', function () {
                var atual = parseFloat(campoNumero.value.replace(',', '.')) || 0;
                definirValorCompleto(atual + parseFloat(botaoPasso.dataset.passo));
            });
        });
    });
})();
</script>
