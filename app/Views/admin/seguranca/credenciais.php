<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
    // Fase 35: NENHUM valor de campo sigiloso chega aqui. O repositorio
    // devolve 'valor' => null para esses campos e entrega apenas a impressao
    // digital - assim nao existe caminho pelo qual o segredo va parar no
    // HTML por engano, nem mesmo num atributo escondido.
    $rotulos = [
        'client_email' => 'E-mail da Conta de Serviço',
        'private_key' => 'Chave privada (PEM)',
        'token_uri' => 'Endereço do token (opcional)',
        'client_id' => 'ID do cliente',
        'client_secret' => 'Segredo do cliente',
        'redirect_uri' => 'Endereço de retorno',
        'host' => 'Servidor',
        'port' => 'Porta',
        'user' => 'Usuário',
        'pass' => 'Senha',
        'from_email' => 'E-mail remetente',
        'from_name' => 'Nome do remetente',
    ];
?>
<div class="pagina-titulo-acoes">
    <h1>Segurança 🔐 — credenciais</h1>
</div>

<?php if (!empty($_SESSION['flash'])): ?>
    <p class="flash-mensagem <?php echo classeFlash(); ?>"><?php echo htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['flash']); ?></p>
<?php endif; ?>

<div class="seguranca-aviso">
    Este acesso foi registrado na auditoria e os demais administradores foram notificados.
    Os campos de segredo sempre abrem <strong>vazios</strong>: deixe em branco para manter o valor atual.
</div>

<?php if (!$chaveMestraConfigurada): ?>
    <div class="seguranca-aviso critico">
        <strong>Não há chave-mestra configurada.</strong>
        Sem ela o sistema não consegue ler nem gravar credencial nenhuma nesta tela — as integrações
        seguem funcionando pelos valores de <code>config/local.php</code>.
        Preencha <code>['cifra']['chave_mestra']</code> nesse arquivo para habilitar esta tela.
    </div>
<?php elseif (!$possuiCredencialNoBanco): ?>
    <div class="seguranca-aviso">
        Nenhuma credencial foi transferida para o banco ainda. As integrações estão usando os valores
        de <code>config/local.php</code>. Grave abaixo, ou use o roteiro
        <code>database/migrar_credenciais.php</code> para transferir tudo de uma vez.
    </div>
<?php endif; ?>

<?php if ($resultadoTeste !== null): ?>
    <p class="flash-mensagem <?php echo $resultadoTeste['ok'] ? 'sucesso' : 'erro'; ?>">
        <?php echo htmlspecialchars($resultadoTeste['mensagem'], ENT_QUOTES, 'UTF-8'); ?>
    </p>
<?php endif; ?>

<?php foreach ($grupos as $grupo): ?>
    <section class="seguranca-grupo">
        <h2><?php echo htmlspecialchars($grupo['rotulo'], ENT_QUOTES, 'UTF-8'); ?></h2>

        <form method="post" action="<?php echo url('seguranca/salvar'); ?>"><?= campoCsrf() ?>
            <input type="hidden" name="grupo" value="<?php echo htmlspecialchars($grupo['chave'], ENT_QUOTES, 'UTF-8'); ?>">

            <?php foreach ($grupo['campos'] as $campo): ?>
                <?php
                    $sigiloso = in_array($campo, $grupo['sigilosos'], true);
                    $item = isset($grupo['itens'][$campo]) ? $grupo['itens'][$campo] : null;
                    $rotulo = isset($rotulos[$campo]) ? $rotulos[$campo] : $campo;
                ?>
                <div class="seguranca-campo">
                    <label for="campo-<?php echo htmlspecialchars($grupo['chave'] . '-' . $campo, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8'); ?>
                        <?php if ($sigiloso): ?><span class="seguranca-etiqueta">segredo</span><?php endif; ?>
                    </label>

                    <?php if ($item !== null && $item['preenchido']): ?>
                        <p class="seguranca-estado">
                            <?php if ($sigiloso): ?>
                                <?php if ($item['ilegivel']): ?>
                                    <strong class="seguranca-ilegivel">Não foi possível ler este valor.</strong>
                                    A chave-mestra provavelmente foi trocada — recadastre o segredo abaixo.
                                <?php else: ?>
                                    <code><?php echo htmlspecialchars($item['impressao_digital'], ENT_QUOTES, 'UTF-8'); ?></code>
                                <?php endif; ?>
                            <?php else: ?>
                                <code><?php echo htmlspecialchars((string) $item['valor'], ENT_QUOTES, 'UTF-8'); ?></code>
                            <?php endif; ?>
                            <?php if (!empty($item['atualizado_por_nome'])): ?>
                                <span class="seguranca-autoria">
                                    alterado por <?php echo htmlspecialchars($item['atualizado_por_nome'], ENT_QUOTES, 'UTF-8'); ?>
                                    em <?php echo htmlspecialchars(formatarDataHora($item['atualizado_em']), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            <?php endif; ?>
                        </p>
                    <?php else: ?>
                        <p class="seguranca-estado"><span class="seguranca-vazio">não configurado no banco</span></p>
                    <?php endif; ?>

                    <?php if ($campo === 'private_key'): ?>
                        <textarea id="campo-<?php echo htmlspecialchars($grupo['chave'] . '-' . $campo, ENT_QUOTES, 'UTF-8'); ?>"
                                  name="<?php echo htmlspecialchars($campo, ENT_QUOTES, 'UTF-8'); ?>"
                                  rows="6" autocomplete="off" spellcheck="false"
                                  placeholder="Cole aqui o conteúdo do campo &quot;private_key&quot; do arquivo .json — deixe em branco para manter"></textarea>
                    <?php else: ?>
                        <input type="text"
                               id="campo-<?php echo htmlspecialchars($grupo['chave'] . '-' . $campo, ENT_QUOTES, 'UTF-8'); ?>"
                               name="<?php echo htmlspecialchars($campo, ENT_QUOTES, 'UTF-8'); ?>"
                               autocomplete="off" spellcheck="false"
                               value="<?php echo $sigiloso || $item === null ? '' : htmlspecialchars((string) $item['valor'], ENT_QUOTES, 'UTF-8'); ?>"
                               placeholder="<?php echo $sigiloso ? 'deixe em branco para manter o valor atual' : ''; ?>">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="form-acoes">
                <button type="submit">Gravar <?php echo htmlspecialchars($grupo['rotulo'], ENT_QUOTES, 'UTF-8'); ?></button>
            </div>
        </form>

        <?php if ($grupo['chave'] === 'google_service_account'): ?>
            <form method="post" action="<?php echo url('seguranca/testar'); ?>" class="seguranca-teste"><?= campoCsrf() ?>
                <label for="email-teste">Testar a conexão consultando a agenda de:</label>
                <input type="email" id="email-teste" name="email_teste" placeholder="seu-email@tjrr.jus.br" required>
                <button type="submit" class="btn-acao">Testar conexão com o Google</button>
                <p class="seguranca-estado">Só leitura — lista as agendas visíveis, não cria nem altera nada.</p>
            </form>
        <?php endif; ?>
    </section>
<?php endforeach; ?>
