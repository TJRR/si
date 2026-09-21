<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Tema</h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('tema/novo'); ?>" class="btn-acao">Novo tema</a>
    </div>
</div>

<?php if (!empty($_SESSION['flash'])): ?>
    <p class="flash-mensagem <?php echo classeFlash(); ?>"><?php echo htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['flash']); ?></p>
<?php endif; ?>

<p style="color:#555;font-size:0.9em;">Cada usuário escolhe, em "Meu Perfil", qual destes temas publicados usar. Quem não escolher nenhum vê o tema marcado como padrão.</p>

<table border="1" cellpadding="6">
    <tr><th>Amostra</th><th>Nome</th><th>Situação</th><th>Em uso por</th><th>Ações</th></tr>
    <?php foreach ($temas as $tema): ?>
    <tr>
        <td>
            <span class="tema-amostra-degrade" style="--tema-amostra-inicio:<?php echo htmlspecialchars($tema['cor_primaria_inicio'], ENT_QUOTES, 'UTF-8'); ?>;--tema-amostra-fim:<?php echo htmlspecialchars($tema['cor_terciaria'], ENT_QUOTES, 'UTF-8'); ?>;"></span>
            <span class="tema-amostra-cor" style="--tema-amostra-cor:<?php echo htmlspecialchars($tema['cor_secundaria'], ENT_QUOTES, 'UTF-8'); ?>;"></span>
            <span class="tema-amostra-cor" style="--tema-amostra-cor:<?php echo htmlspecialchars($tema['cor_destaque_app'], ENT_QUOTES, 'UTF-8'); ?>;"></span>
        </td>
        <td>
            <?php echo htmlspecialchars($tema['nome'], ENT_QUOTES, 'UTF-8'); ?>
            <?php if ((int) $tema['padrao'] === 1): ?>
                <span class="status-pill verde">Padrão</span>
            <?php endif; ?>
        </td>
        <td><?php echo (int) $tema['publicado'] === 1 ? 'Publicado' : 'Privado'; ?></td>
        <td><?php echo (int) $contagens[$tema['id']]; ?></td>
        <td>
            <?php if ((int) $tema['editavel'] === 1): ?>
                <a href="<?php echo url('tema/editar/' . $tema['id']); ?>" class="btn-icone" title="Editar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </a>
            <?php endif; ?>
            <form method="post" action="<?php echo url('tema/duplicar/' . $tema['id']); ?>" style="display:inline;"><?= campoCsrf() ?>
                <button type="submit" class="btn-icone" title="Duplicar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                    </svg>
                </button>
            </form>
            <?php if ((int) $tema['publicado'] === 1): ?>
                <form method="post" action="<?php echo url('tema/despublicar/' . $tema['id']); ?>" style="display:inline;"><?= campoCsrf() ?>
                    <button type="submit" class="btn-icone" title="Despublicar">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </button>
                </form>
                <?php if ((int) $tema['padrao'] === 0): ?>
                    <form method="post" action="<?php echo url('tema/padrao/' . $tema['id']); ?>" style="display:inline;"><?= campoCsrf() ?>
                        <button type="submit" class="btn-icone" title="Definir como padrão">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                            </svg>
                        </button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <form method="post" action="<?php echo url('tema/publicar/' . $tema['id']); ?>" style="display:inline;"><?= campoCsrf() ?>
                    <button type="submit" class="btn-icone" title="Publicar">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    </button>
                </form>
            <?php endif; ?>
            <?php if ((int) $tema['editavel'] === 1 && (int) $tema['padrao'] === 0): ?>
                <form method="post" action="<?php echo url('tema/remover/' . $tema['id']); ?>" style="display:inline;" onsubmit="return confirm('Remover este tema? Quem usa este tema volta para o tema padrão do sistema.');"><?= campoCsrf() ?>
                    <button type="submit" class="btn-icone" title="Remover">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                            <path d="M10 11v6"></path>
                            <path d="M14 11v6"></path>
                            <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                        </svg>
                    </button>
                </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<fieldset>
    <legend>Favicon</legend>
    <p>
        Atual:
        <img src="<?php
            echo !empty($configuracaoVisual['favicon_path'])
                ? htmlspecialchars(config('base_path') . '/assets/' . $configuracaoVisual['favicon_path'], ENT_QUOTES, 'UTF-8')
                : htmlspecialchars(config('base_path') . '/assets/img/favicon-padrao.png', ENT_QUOTES, 'UTF-8');
        ?>" alt="Favicon atual" width="32" height="32">
    </p>
    <form method="post" action="<?php echo url('tema/index'); ?>" enctype="multipart/form-data"><?= campoCsrf() ?>
        <p>
            <label>
                Trocar favicon (PNG):<br>
                <input type="file" name="favicon" accept="image/png">
            </label>
        </p>
        <button type="submit">Salvar favicon</button>
    </form>
</fieldset>
