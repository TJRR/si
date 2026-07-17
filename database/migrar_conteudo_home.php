<?php

/**
 * Fase 18: porta o conteudo hoje vivo em conteudos_site (tabela chave-valor
 * plana, sem concurso_id, usada pela home antiga) para as tabelas novas
 * escopadas por concurso (slides, blocos_conteudo, contatos_concurso), para
 * o concurso ATIVO no momento em que o script roda - sem isso, o conteudo
 * hoje em producao (hero, "sobre", "premiacao", contato) desapareceria da
 * home assim que a Fase 18 entrasse no ar.
 *
 * O que este script NAO faz:
 * - Nao apaga nem altera conteudos_site (tabela preservada, so' deixa de ser
 *   lida pela home nova).
 * - Nao mexe no logo_site (logo por concurso e' feature nova de uma tela
 *   dedicada de Identidade Visual, fora do escopo deste script).
 * - E' idempotente: se rodado mais de uma vez, nao duplica slide nem
 *   sobrescreve um bloco/contato que ja tenha sido editado manualmente apos
 *   a primeira migracao.
 *
 * Por padrao roda em modo consulta (dry-run): so mostra o que seria feito,
 * sem gravar nada. Para gravar de verdade:
 *   php migrar_conteudo_home.php --confirmar
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Repositories\BlocoConteudoRepository;
use App\Repositories\ConcursoRepository;
use App\Repositories\ContatoConcursoRepository;
use App\Repositories\ConteudoSiteRepository;
use App\Repositories\SlideRepository;

$confirmar = in_array('--confirmar', $argv, true);

$concursos = new ConcursoRepository();
$slides = new SlideRepository();
$blocos = new BlocoConteudoRepository();
$contatos = new ContatoConcursoRepository();

$concursoAtivo = $concursos->buscarAtivo();

if ($concursoAtivo === null) {
    echo "ERRO: nenhum concurso com status='ativo' encontrado - nada a migrar.\n";
    exit(1);
}

echo "Concurso ativo: {$concursoAtivo['nome']} (#{$concursoAtivo['id']})\n\n";

$conteudo = (new ConteudoSiteRepository())->listarComoMapa();

// --- Slide (hero) -----------------------------------------------------
echo "=== Slide (hero) ===\n";

$jaTemSlide = count($slides->listarPorConcurso($concursoAtivo['id'])) > 0;
$imagemHero = isset($conteudo['hero_imagem_fundo']) ? $conteudo['hero_imagem_fundo'] : null;

if ($jaTemSlide) {
    echo "  [pulado] este concurso já tem pelo menos 1 slide cadastrado.\n";
} elseif (empty($imagemHero)) {
    echo "  [PENDÊNCIA] conteudos_site.hero_imagem_fundo está vazio - slide não pode ser criado (imagem desktop é obrigatória). Cadastre o slide manualmente pela tela Slideshow.\n";
} else {
    $titulo = isset($conteudo['hero_titulo']) ? (string) $conteudo['hero_titulo'] : '';
    $subtitulo = isset($conteudo['hero_subtitulo']) ? (string) $conteudo['hero_subtitulo'] : '';
    $tituloHtml = '<h1>' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '</h1>'
        . ($subtitulo !== '' ? '<p>' . htmlspecialchars($subtitulo, ENT_QUOTES, 'UTF-8') . '</p>' : '');

    if (!$confirmar) {
        echo "  [seria criado] slide com imagem '{$imagemHero}', título '{$titulo}'.\n";
    } else {
        $slides->criar($concursoAtivo['id'], [
            'imagem_desktop_path' => $imagemHero,
            'imagem_mobile_path' => null,
            'imagem_alt' => $titulo !== '' ? $titulo : 'Imagem principal',
            'titulo_html' => $tituloHtml,
            'separador_cor' => null,
            'cta_titulo' => null,
            'cta_link' => null,
            'cta_target' => '_self',
            'cta_cor_fundo' => null,
            'cta_cor_texto' => null,
            'cta_tamanho' => 'medio',
            'cta_efeito_hover' => 'nenhum',
            'cta_animacao_entrada' => null,
            'ativo' => 1,
        ]);
        echo "  [ok] slide criado.\n";
    }
}

// --- Blocos padrao (Sobre/Premiacao) -----------------------------------
echo "\n=== Blocos padrão (Sobre/Premiação) ===\n";

if ($confirmar) {
    $blocos->garantirBlocosPadrao($concursoAtivo['id']);
}

$mapaOrigem = [
    'sobre' => ['texto' => 'sobre_texto', 'imagem' => 'sobre_imagem'],
    'premiacao' => ['texto' => 'premiacao_texto', 'imagem' => 'premiacao_imagem'],
];

foreach ($mapaOrigem as $chave => $origem) {
    $blocoAtual = $blocos->buscarPorConcursoEChave($concursoAtivo['id'], $chave);
    $textoOrigem = isset($conteudo[$origem['texto']]) ? (string) $conteudo[$origem['texto']] : '';
    $imagemOrigem = isset($conteudo[$origem['imagem']]) ? $conteudo[$origem['imagem']] : null;

    if ($blocoAtual !== null && trim((string) $blocoAtual['conteudo_html']) !== '') {
        echo "  [pulado] bloco '{$chave}' já tem conteúdo (provavelmente editado manualmente).\n";
        continue;
    }

    if (!$confirmar) {
        echo "  [seria atualizado] bloco '{$chave}' com o texto de '{$origem['texto']}'" . ($imagemOrigem ? " + imagem '{$origem['imagem']}'" : '') . ".\n";
        continue;
    }

    if ($blocoAtual === null) {
        echo "  [AVISO] bloco '{$chave}' não encontrado mesmo após garantirBlocosPadrao() - pulado.\n";
        continue;
    }

    $blocos->atualizar($blocoAtual['id'], [
        'titulo' => $blocoAtual['titulo'],
        'conteudo_html' => '<p>' . nl2br(htmlspecialchars($textoOrigem, ENT_QUOTES, 'UTF-8')) . '</p>',
        'imagem_path' => $imagemOrigem ?: null,
        'imagem_alt' => $imagemOrigem ? $blocoAtual['titulo'] : null,
        'cta_titulo' => null,
        'cta_link' => null,
        'secao_ancora' => $blocoAtual['secao_ancora'],
        'ativo' => 1,
    ]);
    echo "  [ok] bloco '{$chave}' atualizado.\n";
}

// --- Contato ------------------------------------------------------------
echo "\n=== Contato ===\n";

$contatoAtual = $contatos->buscarPorConcurso($concursoAtivo['id']);

if ($contatoAtual !== null) {
    echo "  [pulado] este concurso já tem contato cadastrado.\n";
} else {
    $email = isset($conteudo['contato_email']) ? (string) $conteudo['contato_email'] : '';
    $telefone = isset($conteudo['contato_telefone']) ? (string) $conteudo['contato_telefone'] : '';
    $endereco = isset($conteudo['contato_endereco']) ? (string) $conteudo['contato_endereco'] : '';

    if (!$confirmar) {
        echo "  [seria criado] contato com e-mail '{$email}', telefone '{$telefone}'.\n";
    } else {
        $contatos->salvar($concursoAtivo['id'], [
            'email' => $email !== '' ? $email : null,
            'telefone' => $telefone !== '' ? $telefone : null,
            'whatsapp' => null,
            'endereco' => $endereco !== '' ? $endereco : null,
            'redes_sociais' => [],
            'formulario_contato_ativo' => 0,
        ]);
        echo "  [ok] contato criado.\n";
    }
}

echo "\n";

if (!$confirmar) {
    echo "Modo consulta (dry-run). Nada foi gravado.\n";
    echo "Para gravar de verdade, rode: php migrar_conteudo_home.php --confirmar\n";
} else {
    echo "Migração concluída.\n";
}
