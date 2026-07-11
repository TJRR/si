<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Parcial da arvore lateral do painel "Concursos". So roda no primeiro
 * carregamento de uma pagina (nunca em requisicao parcial — ver
 * View::renderizarParcial()), pre-expandindo os ancestrais do no atual
 * ($caminhoArvore, vindo de NavegacaoService::caminhoAte()) diretamente em
 * HTML. Os demais irmaos ficam fechados e so carregam via
 * assets/js/navegacao-arvore.js quando o usuario clica na seta.
 */

function admin_arvore_no(array $no, $ativo, $expandido, $filhosHtml)
{
    $classes = 'arvore-no' . ($ativo ? ' ativo' : '');

    if ($no['folha']) {
        $caretHtml = '<span class="arvore-spacer"></span>';
    } else {
        $caretHtml = '<button type="button" class="arvore-caret' . ($expandido ? ' aberto' : '') . '" aria-label="Expandir/recolher">▸</button>';
    }

    $html = '<li class="' . $classes . '" data-tipo="' . htmlspecialchars($no['tipo'], ENT_QUOTES, 'UTF-8') . '" data-id="' . (int) $no['id'] . '">';
    $html .= '<div class="arvore-linha">' . $caretHtml;
    $html .= '<a href="' . url($no['url']) . '" class="arvore-rotulo">' . htmlspecialchars($no['rotulo'], ENT_QUOTES, 'UTF-8') . '</a></div>';

    if (!$no['folha']) {
        $classeFilhos = 'arvore-filhos' . ($expandido ? '' : ' arvore-filhos-fechado');
        $html .= '<ul class="' . $classeFilhos . '">' . $filhosHtml . '</ul>';
    }

    return $html . '</li>';
}

function admin_arvore_renderizar_nivel(array $nos, array $caminhoRestante, $tipoDestacado, $idDestacado)
{
    $proximoDoCaminho = !empty($caminhoRestante) ? $caminhoRestante[0] : null;
    $html = '';

    foreach ($nos as $no) {
        $ehProximoDoCaminho = $proximoDoCaminho !== null
            && $proximoDoCaminho['tipo'] === $no['tipo']
            && (int) $proximoDoCaminho['id'] === (int) $no['id'];

        $ativo = $tipoDestacado !== null && $no['tipo'] === $tipoDestacado && (int) $no['id'] === (int) $idDestacado;
        $expandido = false;
        $filhosHtml = '';

        if ($ehProximoDoCaminho && !$no['folha']) {
            $expandido = true;
            $filhosDoNo = \App\Services\NavegacaoService::filhosDe($no['tipo'], $no['id']);
            $filhosHtml = admin_arvore_renderizar_nivel($filhosDoNo, array_slice($caminhoRestante, 1), $tipoDestacado, $idDestacado);
        }

        $html .= admin_arvore_no($no, $ativo, $expandido, $filhosHtml);
    }

    return $html;
}

$arvoreRaiz = \App\Services\NavegacaoService::filhosDe('raiz', null);
$noDestacado = !empty($caminhoArvore) ? end($caminhoArvore) : null;
?>
<nav id="arvore-admin" aria-label="Navegação de concursos">
    <ul class="arvore-raiz">
        <?php if (empty($arvoreRaiz)): ?>
            <li class="arvore-vazio">Nenhum concurso cadastrado ainda.</li>
        <?php else: ?>
            <?php echo admin_arvore_renderizar_nivel(
                $arvoreRaiz,
                $caminhoArvore,
                $noDestacado !== null ? $noDestacado['tipo'] : null,
                $noDestacado !== null ? $noDestacado['id'] : null
            ); ?>
        <?php endif; ?>
    </ul>
</nav>
