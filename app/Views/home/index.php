<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
$heroTitulo = isset($conteudo['hero_titulo']) ? (string) $conteudo['hero_titulo'] : '';
$heroSubtitulo = isset($conteudo['hero_subtitulo']) ? (string) $conteudo['hero_subtitulo'] : '';
$sobreTexto = isset($conteudo['sobre_texto']) ? (string) $conteudo['sobre_texto'] : '';
$premiacaoTexto = isset($conteudo['premiacao_texto']) ? (string) $conteudo['premiacao_texto'] : '';
$contatoEmail = isset($conteudo['contato_email']) ? (string) $conteudo['contato_email'] : '';
$contatoTelefone = isset($conteudo['contato_telefone']) ? (string) $conteudo['contato_telefone'] : '';
$contatoEndereco = isset($conteudo['contato_endereco']) ? (string) $conteudo['contato_endereco'] : '';

$logoSrc = !empty($conteudo['logo_site'])
    ? config('base_path') . '/assets/' . $conteudo['logo_site']
    : config('base_path') . '/assets/img/logo-padrao.png';
$heroImagemFundo = !empty($conteudo['hero_imagem_fundo']) ? config('base_path') . '/assets/' . $conteudo['hero_imagem_fundo'] : null;
$sobreImagem = !empty($conteudo['sobre_imagem']) ? config('base_path') . '/assets/' . $conteudo['sobre_imagem'] : null;
$premiacaoImagem = !empty($conteudo['premiacao_imagem']) ? config('base_path') . '/assets/' . $conteudo['premiacao_imagem'] : null;
?>
<div class="site-page">
    <header class="site-header">
        <div class="site-header-inner">
            <img src="<?php echo htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Prêmio de Inovação TJRR" class="site-logo">
            <nav class="site-nav">
                <a href="#cronograma">Cronograma</a>
                <a href="#temas">Temas</a>
                <a href="#premiacao">Premiação</a>
                <a href="#contato">Contato</a>
                <a href="<?php echo url('auth/login'); ?>" class="btn btn-bordered">Entrar</a>
            </nav>
        </div>
    </header>

    <section class="hero"<?php echo $heroImagemFundo !== null ? ' style="background-image:url(\'' . htmlspecialchars($heroImagemFundo, ENT_QUOTES, 'UTF-8') . '\')"' : ''; ?>>
        <div class="hero-shape" aria-hidden="true"></div>
        <div class="hero-conteudo">
            <h1 class="hero-title"><?php echo htmlspecialchars($heroTitulo, ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="hero-subtitle"><?php echo htmlspecialchars($heroSubtitulo, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php if (empty($trilhasComInscricaoAberta)): ?>
                <p class="hero-subtitle">Inscrições encerradas no momento.</p>
            <?php elseif (count($trilhasComInscricaoAberta) === 1): ?>
                <a href="<?php echo url('inscricao/formulario/' . (int) $trilhasComInscricaoAberta[0]['etapa_id']); ?>" class="btn btn-bordered-white btn-large">Inscreva-se</a>
            <?php else: ?>
                <?php foreach ($trilhasComInscricaoAberta as $item): ?>
                    <a href="<?php echo url('inscricao/formulario/' . (int) $item['etapa_id']); ?>" class="btn btn-bordered-white btn-large">
                        Inscreva-se — <?php echo htmlspecialchars($item['trilha_nome'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="site-section site-section-com-imagem" id="sobre">
        <?php if ($sobreImagem !== null): ?>
            <img src="<?php echo htmlspecialchars($sobreImagem, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="section-imagem">
        <?php endif; ?>
        <div>
            <h2 class="section-title">Sobre o Prêmio</h2>
            <p class="section-text"><?php echo nl2br(htmlspecialchars($sobreTexto, ENT_QUOTES, 'UTF-8')); ?></p>
        </div>
    </section>

    <section class="site-section site-section-alt" id="cronograma">
        <div class="site-section-inner">
            <h2 class="section-title">Cronograma</h2>
            <?php if (empty($cronograma)): ?>
                <p class="section-text">Cronograma em definição.</p>
            <?php else: ?>
                <ol class="timeline">
                    <?php foreach ($cronograma as $etapa): ?>
                        <li class="timeline-item">
                            <span class="timeline-trilha"><?php echo htmlspecialchars($etapa['trilha_nome'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <strong class="timeline-nome"><?php echo htmlspecialchars($etapa['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span class="timeline-datas">
                                <?php echo htmlspecialchars((string) $etapa['data_inicio'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php if ($etapa['data_fim']): ?>
                                    a <?php echo htmlspecialchars((string) $etapa['data_fim'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php endif; ?>
                            </span>
                            <?php if (!empty($etapa['descricao'])): ?>
                                <p class="timeline-descricao"><?php echo htmlspecialchars($etapa['descricao'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>
    </section>

    <?php if (!empty($etapasComResultadoPublicado)): ?>
        <section class="site-section" id="resultados">
            <h2 class="section-title">Resultados</h2>
            <ul>
                <?php foreach ($etapasComResultadoPublicado as $item): ?>
                    <li>
                        <a href="<?php echo url('resultadosPublicos/etapa/' . (int) $item['etapa_id']); ?>">
                            <?php echo htmlspecialchars($item['trilha_nome'] . ' — ' . $item['etapa_nome'], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <section class="site-section" id="temas">
        <h2 class="section-title">Temas e Desafios</h2>
        <?php if (empty($temasPorTrilha)): ?>
            <p class="section-text">Temas em definição.</p>
        <?php endif; ?>
        <?php foreach ($temasPorTrilha as $grupo): ?>
            <?php if (!empty($grupo['temas'])): ?>
                <h3 class="temas-trilha-titulo"><?php echo htmlspecialchars($grupo['trilha']['nome'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <div class="temas-grid">
                    <?php foreach ($grupo['temas'] as $tema): ?>
                        <div class="tema-card">
                            <h4 class="tema-nome"><?php echo htmlspecialchars($tema['nome'], ENT_QUOTES, 'UTF-8'); ?></h4>
                            <?php if (!empty($tema['descricao_longa'])): ?>
                                <p class="tema-descricao"><?php echo nl2br(htmlspecialchars($tema['descricao_longa'], ENT_QUOTES, 'UTF-8')); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </section>

    <section class="site-section site-section-alt" id="premiacao">
        <div class="site-section-inner site-section-com-imagem">
            <?php if ($premiacaoImagem !== null): ?>
                <img src="<?php echo htmlspecialchars($premiacaoImagem, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="section-imagem">
            <?php endif; ?>
            <div>
                <h2 class="section-title">Premiação</h2>
                <p class="section-text"><?php echo nl2br(htmlspecialchars($premiacaoTexto, ENT_QUOTES, 'UTF-8')); ?></p>
            </div>
        </div>
    </section>

    <footer class="site-footer" id="contato">
        <h2 class="section-title">Contato</h2>
        <?php if ($contatoEmail !== ''): ?>
            <p>E-mail: <a href="mailto:<?php echo htmlspecialchars($contatoEmail, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($contatoEmail, ENT_QUOTES, 'UTF-8'); ?></a></p>
        <?php endif; ?>
        <?php if ($contatoTelefone !== ''): ?>
            <p>Telefone: <?php echo htmlspecialchars($contatoTelefone, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <?php if ($contatoEndereco !== ''): ?>
            <p><?php echo htmlspecialchars($contatoEndereco, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <p class="site-footer-links">
            <a href="<?php echo config('base_path'); ?>/politica.php">Política de Privacidade</a>
            &nbsp;|&nbsp;
            <a href="<?php echo config('base_path'); ?>/termos.php">Termos de Serviço</a>
        </p>
    </footer>
</div>
