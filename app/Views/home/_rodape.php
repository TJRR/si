<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<footer class="site-footer" id="contato">
    <h2 class="section-title">Contato</h2>

    <?php if ($contato !== null): ?>
        <div class="site-rodape-info">
            <?php if (!empty($contato['email'])): ?>
                <p>E-mail: <a href="mailto:<?php echo htmlspecialchars($contato['email'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($contato['email'], ENT_QUOTES, 'UTF-8'); ?></a></p>
            <?php endif; ?>
            <?php if (!empty($contato['telefone'])): ?>
                <p>Telefone: <?php echo htmlspecialchars($contato['telefone'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            <?php if (!empty($contato['whatsapp'])): ?>
                <p>WhatsApp: <a href="https://wa.me/<?php echo htmlspecialchars(preg_replace('/\D/', '', $contato['whatsapp']), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($contato['whatsapp'], ENT_QUOTES, 'UTF-8'); ?></a></p>
            <?php endif; ?>
            <?php if (!empty($contato['endereco'])): ?>
                <p><?php echo nl2br(htmlspecialchars($contato['endereco'], ENT_QUOTES, 'UTF-8')); ?></p>
            <?php endif; ?>
            <?php if (!empty($contato['redes_sociais'])): ?>
                <p class="site-rodape-redes">
                    <?php foreach ($contato['redes_sociais'] as $rede => $link): ?>
                        <a href="<?php echo htmlspecialchars($link, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" aria-label="<?php echo htmlspecialchars(ucfirst($rede), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(ucfirst($rede), ENT_QUOTES, 'UTF-8'); ?></a>
                    <?php endforeach; ?>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($contato !== null && !empty($contato['formulario_contato_ativo'])): ?>
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
    <?php endif; ?>

    <p class="site-footer-links">
        <a href="<?php echo config('base_path'); ?>/politica.php">Política de Privacidade</a>
        &nbsp;|&nbsp;
        <a href="<?php echo config('base_path'); ?>/termos.php">Termos de Serviço</a>
        &nbsp;|&nbsp;
        <a href="<?php echo url('edicoes/index'); ?>">Edições Anteriores</a>
    </p>
    <p class="site-footer-copyright">© <?php echo date('Y'); ?> Tribunal de Justiça do Estado de Roraima — Sistema de Gestão do Prêmio de Inovação.</p>
</footer>
