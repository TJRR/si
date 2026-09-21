<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Configurações</h1>
    <div class="pagina-titulo-botoes">
        <button type="submit" form="form-configuracoes">Salvar</button>
        <a href="<?php echo url('home/administrativo'); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('configuracoes/index'); ?>" id="form-configuracoes"><?= campoCsrf() ?>
    <label>Tempo de expiração de sessão por inatividade (minutos):
        <input type="number" name="sessao_timeout_minutos" min="1" value="<?php echo (int) $configuracao['sessao_timeout_minutos']; ?>">
    </label><br>
</form>

<h2>Identidade institucional</h2>
<p>Nome da instituição e da unidade responsável, usados em todas as telas, e-mails e títulos do sistema no lugar de um nome fixo. Cada uma tem duas formas: o nome completo (para textos formais, como o rodapé) e a sigla (para menções curtas).</p>

<form method="post" action="<?php echo url('configuracoes/salvarIdentidade'); ?>">
    <?= campoCsrf() ?>
    <label>
        Nome da instituição (até 255 caracteres):
        <input type="text" name="instituicao_nome_completo" maxlength="255" required value="<?php echo htmlspecialchars((string) $configuracao['instituicao_nome_completo'], ENT_QUOTES, 'UTF-8'); ?>">
    </label>
    <br><small>Nome por extenso, usado em textos formais como o rodapé das páginas.</small>
    <br><br>
    <label>
        Sigla da instituição (até 150 caracteres):
        <input type="text" name="instituicao" maxlength="150" required value="<?php echo htmlspecialchars((string) $configuracao['instituicao'], ENT_QUOTES, 'UTF-8'); ?>">
    </label>
    <br><small>Aparece em telas públicas, e-mails e no título das páginas.</small>
    <br><br>
    <label>
        Nome da unidade responsável (até 255 caracteres):
        <input type="text" name="unidade_responsavel_nome_completo" maxlength="255" required value="<?php echo htmlspecialchars((string) $configuracao['unidade_responsavel_nome_completo'], ENT_QUOTES, 'UTF-8'); ?>">
    </label>
    <br><small>Nome por extenso do setor ou núcleo responsável pelo sistema.</small>
    <br><br>
    <label>
        Sigla da unidade responsável (até 150 caracteres):
        <input type="text" name="unidade_responsavel" maxlength="150" required value="<?php echo htmlspecialchars((string) $configuracao['unidade_responsavel'], ENT_QUOTES, 'UTF-8'); ?>">
    </label>
    <br><small>Citada quando o usuário precisa entrar em contato.</small>
    <br><br>
    <button type="submit">Salvar identidade institucional</button>
</form>

<h2>Aplicativo (PWA)</h2>
<p>Identidade do aplicativo web instalável do Evento: nome exibido na tela inicial do celular ou computador, e ícone. Sem essas configurações, o aplicativo usa um nome e um ícone padrão.</p>

<form method="post" action="<?php echo url('configuracoes/salvarApp'); ?>">
    <?= campoCsrf() ?>
    <label>
        Nome do aplicativo (até 60 caracteres):
        <input type="text" name="nome_app" maxlength="60" value="<?php echo htmlspecialchars((string) $configuracao['nome_app'], ENT_QUOTES, 'UTF-8'); ?>">
    </label>
    <br><small>Nome completo do aplicativo, mostrado na tela de instalação.</small>
    <br><br>
    <label>
        Nome curto (até 20 caracteres):
        <input type="text" name="nome_app_curto" maxlength="20" value="<?php echo htmlspecialchars((string) $configuracao['nome_app_curto'], ENT_QUOTES, 'UTF-8'); ?>">
    </label>
    <br><small>É o que aparece embaixo do ícone na tela inicial do celular/computador; nomes muito longos são cortados pelo sistema operacional.</small>
    <br><br>
    <button type="submit">Salvar nome do aplicativo</button>
</form>

<form method="post" action="<?php echo url('configuracoes/enviarIconeApp'); ?>" enctype="multipart/form-data" style="margin-top:1.5em;">
    <?= campoCsrf() ?>
    <?php if (!empty($configuracao['icone_app_atualizado_em'])): ?>
        <p>
            Ícone atual:
            <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/uploads/conteudo/icone-app/icon-192.png?v=' . strtotime($configuracao['icone_app_atualizado_em']), ENT_QUOTES, 'UTF-8'); ?>" alt="Ícone do aplicativo" width="64" height="64">
        </p>
    <?php else: ?>
        <p>Nenhum ícone próprio enviado ainda; o aplicativo está usando o ícone padrão do sistema.</p>
    <?php endif; ?>
    <label>
        Enviar novo ícone:
        <input type="file" name="icone_app" accept="image/png,image/jpeg,image/webp,image/gif">
    </label>
    <br><small>Envie uma imagem <strong>quadrada</strong> (largura igual à altura), mínimo 512×512 pixels (recomendado 1024×1024). Evite deixar a borda muito próxima do desenho: o Android recorta o ícone em círculo em alguns aparelhos.</small>
    <br><br>
    <button type="submit">Enviar ícone</button>
</form>

<h2>Modo de manutenção</h2>
<?php if (isset($configuracao['sistema_desativado']) && (int) $configuracao['sistema_desativado'] === 1): ?>
    <p><strong style="color:red;">Sistema em manutenção.</strong> Apenas administradores conseguem acessar; todos os outros usuários são desconectados na próxima ação que tentarem.</p>
    <form method="post" action="<?php echo url('configuracoes/reativarSistema'); ?>" onsubmit="return confirm('Reativar o sistema? O acesso normal volta para todos os usuários imediatamente.');"><?= campoCsrf() ?>
        <button type="submit">Reativar sistema</button>
    </form>
<?php else: ?>
    <p>Sistema ativo, funcionando normalmente para todos os perfis.</p>
    <form method="post" action="<?php echo url('configuracoes/desativarSistema'); ?>" onsubmit="return confirm('Desativar o sistema? Isso vai bloquear o acesso e desconectar TODOS os usuários, exceto administradores, até que o sistema seja reativado. Use apenas durante uma atualização.');"><?= campoCsrf() ?>
        <button type="submit">Desativar sistema</button>
    </form>
<?php endif; ?>
