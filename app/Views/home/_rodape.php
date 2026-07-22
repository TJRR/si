<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<footer class="site-footer" id="contato">
    <div class="site-footer-colunas">
        <div class="site-footer-coluna">
            <img src="<?php echo htmlspecialchars(!empty($configVisual['rodape_logo_path']) ? config('base_path') . '/assets/' . $configVisual['rodape_logo_path'] : $logoSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Prêmio de Inovação TJRR" class="site-footer-logo">
        </div>

        <?php if ($contato !== null && !empty($contato['texto_institucional'])): ?>
        <div class="site-footer-coluna site-footer-texto">
            <?php echo $contato['texto_institucional']; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($menuRodape)): ?>
        <nav class="site-footer-coluna" aria-label="Mapa do site">
            <h2>Navegação</h2>
            <ul>
                <?php foreach ($menuRodape as $item): ?>
                    <li>
                        <?php if (!empty($item['externa'])): ?>
                            <a href="<?php echo url($item['url']); ?>"><?php echo htmlspecialchars($item['rotulo'], ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php else: ?>
                            <a href="#<?php echo htmlspecialchars($item['ancora'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($item['rotulo'], ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <?php endif; ?>

        <?php if ($contato !== null): ?>
        <address class="site-footer-coluna site-footer-contato">
            <h2>Contato</h2>
            <ul>
                <?php if (!empty($contato['endereco'])): ?>
                    <li>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        <span><?php echo nl2br(htmlspecialchars($contato['endereco'], ENT_QUOTES, 'UTF-8')); ?></span>
                    </li>
                <?php endif; ?>
                <?php if (!empty($contato['telefone'])): ?>
                    <li>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92Z"></path></svg>
                        <span><?php echo htmlspecialchars($contato['telefone'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </li>
                <?php endif; ?>
                <?php if (!empty($contato['whatsapp'])): ?>
                    <li>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2c-5.46 0-9.9 4.44-9.9 9.9 0 1.75.46 3.45 1.32 4.95L2 22l5.28-1.38a9.9 9.9 0 0 0 4.76 1.21h.01c5.46 0 9.9-4.44 9.9-9.9 0-2.64-1.03-5.12-2.9-6.99A9.82 9.82 0 0 0 12.04 2Zm0 1.67c2.19 0 4.25.85 5.79 2.4a8.2 8.2 0 0 1 2.41 5.83c0 4.55-3.7 8.24-8.24 8.24a8.2 8.2 0 0 1-4.19-1.15l-.3-.18-3.13.82.84-3.05-.2-.31a8.18 8.18 0 0 1-1.26-4.37c0-4.55 3.7-8.23 8.24-8.23h.04Z"></path></svg>
                        <a href="https://wa.me/<?php echo htmlspecialchars(preg_replace('/\D/', '', $contato['whatsapp']), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($contato['whatsapp'], ENT_QUOTES, 'UTF-8'); ?></a>
                    </li>
                <?php endif; ?>
                <?php if (!empty($contato['email'])): ?>
                    <li>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16v16H4z" opacity="0"></path><path d="M22 6 12 13 2 6"></path><rect x="2" y="4" width="20" height="16" rx="2"></rect></svg>
                        <a href="mailto:<?php echo htmlspecialchars($contato['email'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($contato['email'], ENT_QUOTES, 'UTF-8'); ?></a>
                    </li>
                <?php endif; ?>
                <?php if (!empty($contato['redes_sociais'])): ?>
                    <li class="site-footer-redes">
                        <?php foreach ($contato['redes_sociais'] as $rede => $link): ?>
                            <a href="<?php echo htmlspecialchars($link, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" aria-label="<?php echo htmlspecialchars(ucfirst($rede), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(ucfirst($rede), ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php endforeach; ?>
                    </li>
                <?php endif; ?>
            </ul>
        </address>
        <?php endif; ?>
    </div>

    <?php if ($contato !== null && !empty($contato['formulario_contato_ativo'])): ?>
        <div class="site-footer-colunas">
            <form method="post" action="<?php echo url('home/enviarContato/' . (int) $concursoAtivo['id']); ?>" class="site-formulario-contato">
                <h3>Fale conosco</h3>
                <label for="contato-nome">Nome</label>
                <input type="text" id="contato-nome" name="nome" required>

                <label for="contato-email">E-mail</label>
                <input type="email" id="contato-email" name="email" required>

                <label for="contato-mensagem">Mensagem</label>
                <textarea id="contato-mensagem" name="mensagem" rows="4" required></textarea>

                <button type="submit" class="btn btn-cta">Enviar</button>
            </form>
        </div>
    <?php endif; ?>

    <div class="site-footer-barra">
        <p class="site-footer-copyright">© <?php echo date('Y'); ?> Tribunal de Justiça do Estado de Roraima — Sistema de Gestão do Prêmio de Inovação.</p>
        <p class="site-footer-links">
            <a href="<?php echo config('base_path'); ?>/politica.php">Política de Privacidade</a>
            &nbsp;|&nbsp;
            <a href="<?php echo config('base_path'); ?>/termos.php">Termos de Serviço</a>
            &nbsp;|&nbsp;
            <a href="<?php echo url('edicoes/index'); ?>">Edições Anteriores</a>
            &nbsp;|&nbsp;
            <a href="<?php echo url('mentoriaPublica/index'); ?>">Mentorias</a>
        </p>
        <a href="#topo" class="site-footer-topo" aria-label="Voltar ao topo">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 15l7-7 7 7"></path></svg>
        </a>
    </div>
</footer>
