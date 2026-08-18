<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
    $rotulosStatus = ['recebida' => 'Recebida', 'escalada' => 'Em análise', 'respondida' => 'Respondida'];
    $coresStatus = ['recebida' => 'azul', 'escalada' => 'laranja', 'respondida' => 'verde'];

    // iniciaisAvatar() vem de app/helpers.php (compartilhada com
    // participante/duvida_ver.php). duvida_renderizar_anexo() fica local
    // (echoa HTML, nao e' um helper de dado) - guardada com function_exists
    // porque o mesmo nome existe em participante/duvida_ver.php e, embora o
    // roteador nunca inclua as duas views no mesmo request hoje, e' barato
    // evitar o "Cannot redeclare function" se isso um dia mudar.
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
    // quando o usuario ja fez upload - iniciais so' entram como reserva
    // pra quem nunca enviou foto. Mesmo guard de duvida_renderizar_anexo().
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
    <a href="<?php echo url(\App\Core\Auth::destinoPainel()); ?>" class="btn-voltar">Voltar</a>
    <h1>Dúvida #<?php echo (int) $duvida['id']; ?></h1>
    <span class="status-pill <?php echo $coresStatus[$duvida['status']]; ?>"><?php echo $rotulosStatus[$duvida['status']]; ?></span>
    <?php if ($atrasada): ?>
        <span class="status-pill vermelho" title="Mais de 48h sem resposta">Atrasada</span>
    <?php endif; ?>
    <?php if ($duvida['status'] === 'escalada'): ?>
        <span class="duvida-responsavel-atual">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            Com <?php echo htmlspecialchars($duvida['responsavel_nome'], ENT_QUOTES, 'UTF-8'); ?>
        </span>
    <?php endif; ?>
</div>

<?php if (!empty($_SESSION['flash'])): ?>
    <p class="flash-mensagem <?php echo classeFlash(); ?>"><?php echo htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['flash']); ?></p>
<?php endif; ?>

<div class="duvida-mensagem">
    <?php duvida_renderizar_avatar($duvida['participante_nome'], $duvida['participante_foto_path']); ?>
    <div class="duvida-mensagem-corpo">
        <strong><?php echo htmlspecialchars($duvida['participante_nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
        <p class="duvida-mensagem-meta">Equipe <?php echo htmlspecialchars($duvida['nome_equipe'], ENT_QUOTES, 'UTF-8'); ?> ·
            <?php echo htmlspecialchars($duvida['trilha_nome'], ENT_QUOTES, 'UTF-8'); ?> ·
            <?php echo htmlspecialchars(formatarDataHora($duvida['criado_em']), ENT_QUOTES, 'UTF-8'); ?></p>
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

<?php if ($duvida['status'] !== 'respondida'): ?>
    <div class="duvida-acoes-grid">
        <div class="duvida-acao-card" id="responder">
            <h2>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                </svg>
                Responder
            </h2>
            <form method="post" action="<?php echo url('duvidaAdmin/responder/' . (int) $duvida['id']); ?>" enctype="multipart/form-data"><?= campoCsrf() ?>
                <textarea name="resposta" rows="4" placeholder="Escreva a resposta" required></textarea>
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
                        Enviar resposta
                    </button>
                </div>
                <p class="duvida-limite">PDF, WEBP, JPG, JPEG ou PNG · até <?php echo $limiteMB; ?>MB</p>
            </form>
        </div>

        <div class="duvida-acao-card" id="escalar">
            <h2>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="15 17 20 12 15 7"></polyline>
                    <path d="M4 18v-2a4 4 0 0 1 4-4h12"></path>
                </svg>
                Escalar
            </h2>
            <?php if (empty($atendentesDisponiveis)): ?>
                <p>Nenhum outro administrador/suporte disponível neste concurso.</p>
            <?php else: ?>
                <form method="post" action="<?php echo url('duvidaAdmin/escalar/' . (int) $duvida['id']); ?>"><?= campoCsrf() ?>
                    <label for="duvida-escalar-usuario">Escalar para</label>
                    <select id="duvida-escalar-usuario" name="usuario_id" required>
                        <option value="">Selecione um responsável</option>
                        <?php foreach ($atendentesDisponiveis as $atendente): ?>
                            <option value="<?php echo (int) $atendente['id']; ?>"><?php echo htmlspecialchars($atendente['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="duvida-acao-rodape">
                        <button type="submit" class="btn-acao">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="vertical-align:-2px;margin-right:0.3rem;">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                            Escalar dúvida
                        </button>
                    </div>
                    <p class="duvida-limite">Visível apenas pro responsável designado.</p>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($escalonamentos)): ?>
    <h2 style="display:flex;align-items:center;gap:0.4rem;margin-top:1.5rem;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
        </svg>
        Histórico de escalonamento
    </h2>
    <?php foreach ($escalonamentos as $escalonamento): ?>
        <div class="duvida-historico-item">
            <span>
                <?php echo htmlspecialchars($escalonamento['de_usuario_nome'], ENT_QUOTES, 'UTF-8'); ?>
                →
                <?php echo htmlspecialchars($escalonamento['para_usuario_nome'], ENT_QUOTES, 'UTF-8'); ?>
            </span>
            <span class="duvida-historico-quando">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <?php echo htmlspecialchars(formatarDataHora($escalonamento['criado_em']), ENT_QUOTES, 'UTF-8'); ?>
            </span>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
