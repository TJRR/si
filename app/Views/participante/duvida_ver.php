<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
    $rotulosStatus = ['recebida' => 'Recebida', 'escalada' => 'Em análise', 'respondida' => 'Respondida'];
    $coresStatus = ['recebida' => 'azul', 'escalada' => 'laranja', 'respondida' => 'verde'];

    // iniciaisAvatar() vem de app/helpers.php (compartilhada com
    // admin/duvidas/ver.php). duvida_renderizar_anexo() fica local (echoa
    // HTML, nao e' um helper de dado) - guardada com function_exists porque
    // o mesmo nome existe em admin/duvidas/ver.php.
    if (!function_exists('duvida_renderizar_anexo')) {
        function duvida_renderizar_anexo($path, $nomeOriginal)
        {
            if (empty($path)) {
                return;
            }
            ?>
            <a class="duvida-anexo" href="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $path, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                </svg>
                <?php echo htmlspecialchars($nomeOriginal, ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <?php
        }
    }

    // Fase 29 (ajuste pos-push): foto de "Meu perfil" (usuarios.foto_path),
    // quando o usuario ja fez upload - iniciais so' entram como reserva pra
    // quem nunca enviou foto. Mesmo guard de duvida_renderizar_anexo().
    if (!function_exists('duvida_renderizar_avatar')) {
        function duvida_renderizar_avatar($nome, $fotoPath)
        {
            if (!empty($fotoPath)) {
                ?>
                <img class="duvida-avatar" src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $fotoPath, ENT_QUOTES, 'UTF-8'); ?>" alt="">
                <?php
                return;
            }
            ?>
            <div class="duvida-avatar"><?php echo htmlspecialchars(iniciaisAvatar($nome), ENT_QUOTES, 'UTF-8'); ?></div>
            <?php
        }
    }
?>
<div class="duvida-cabecalho">
    <a href="<?php echo url('duvida/index'); ?>" class="btn-voltar">Voltar</a>
    <h1>Dúvida</h1>
    <span class="status-pill <?php echo $coresStatus[$duvida['status']]; ?>"><?php echo $rotulosStatus[$duvida['status']]; ?></span>
</div>

<?php if (!empty($flash)): ?>
    <p style="color:green;"><?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<div class="duvida-mensagem">
    <?php duvida_renderizar_avatar($duvida['participante_nome'], $duvida['participante_foto_path']); ?>
    <div class="duvida-mensagem-corpo">
        <strong><?php echo htmlspecialchars($duvida['participante_nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
        <p class="duvida-mensagem-meta"><?php echo htmlspecialchars(formatarDataHora($duvida['criado_em']), ENT_QUOTES, 'UTF-8'); ?></p>
        <p class="duvida-mensagem-texto"><?php echo nl2br(htmlspecialchars($duvida['pergunta'], ENT_QUOTES, 'UTF-8')); ?></p>
        <?php duvida_renderizar_anexo($duvida['anexo_path'], $duvida['anexo_nome_original']); ?>
    </div>
</div>

<?php foreach ($respostas as $resposta): ?>
    <div class="duvida-mensagem">
        <?php duvida_renderizar_avatar($resposta['usuario_nome'], $resposta['usuario_foto_path']); ?>
        <div class="duvida-mensagem-corpo">
            <strong><?php echo htmlspecialchars($resposta['usuario_nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
            <p class="duvida-mensagem-meta"><?php echo htmlspecialchars(formatarDataHora($resposta['criado_em']), ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="duvida-mensagem-texto"><?php echo nl2br(htmlspecialchars($resposta['resposta'], ENT_QUOTES, 'UTF-8')); ?></p>
            <?php duvida_renderizar_anexo($resposta['anexo_path'], $resposta['anexo_nome_original']); ?>
        </div>
    </div>
<?php endforeach; ?>

<?php if ($duvida['status'] === 'respondida'): ?>
    <div class="duvida-acoes-grid">
        <div class="duvida-acao-card">
            <h2>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                    <path d="M3 3v5h5"></path>
                </svg>
                Reabrir dúvida
            </h2>
            <p class="duvida-limite" style="margin-top:-0.4rem;margin-bottom:0.6rem;">Se a resposta não resolveu, explique o que ainda falta esclarecer.</p>
            <form method="post" action="<?php echo url('duvida/reabrir/' . (int) $duvida['id']); ?>" enctype="multipart/form-data">
                <textarea name="pergunta" rows="4" placeholder="O que ainda falta esclarecer" required></textarea>
                <div class="duvida-acao-rodape">
                    <label class="btn-icone" title="Anexar arquivo" style="cursor:pointer;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                        </svg>
                        <input type="file" name="anexo" accept="application/pdf,image/webp,image/jpeg,image/png" style="display:none;">
                    </label>
                    <button type="submit" class="btn-acao">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="vertical-align:-2px;margin-right:0.3rem;">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                        Reabrir
                    </button>
                </div>
                <p class="duvida-limite">PDF, WEBP, JPG, JPEG ou PNG · até <?php echo $limiteMB; ?>MB</p>
            </form>
        </div>
    </div>
<?php endif; ?>
