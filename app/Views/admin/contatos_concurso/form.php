<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Contato</h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('contatosConcurso/mensagens'); ?>" class="btn-acao">Ver mensagens recebidas</a>
    </div>
</div>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('contatosConcurso/index'); ?>"><?= campoCsrf() ?>
    <label>E-mail de contato:
        <input type="email" name="email" value="<?php echo htmlspecialchars($contato !== null ? (string) $contato['email'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Telefone:
        <input type="text" name="telefone" value="<?php echo htmlspecialchars($contato !== null ? (string) $contato['telefone'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>WhatsApp:
        <input type="text" name="whatsapp" value="<?php echo htmlspecialchars($contato !== null ? (string) $contato['whatsapp'] : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Endereço:<br>
        <textarea name="endereco" rows="3" cols="50"><?php echo htmlspecialchars($contato !== null ? (string) $contato['endereco'] : '', ENT_QUOTES, 'UTF-8'); ?></textarea>
    </label>

    <?php
    // isset(): coluna de migration nova - um banco ainda nao migrado nao pode
    // derrubar a tela de Contato com Notice.
    $mapaUrlAtual = ($contato !== null && isset($contato['mapa_url'])) ? (string) $contato['mapa_url'] : '';
    ?>
    <label>Link do endereço no mapa:
        <input type="text" name="mapa_url" value="<?php echo htmlspecialchars($mapaUrlAtual, ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://..." size="60">
    </label>
    <p style="color:#555;font-size:0.9em;">
        Com este campo preenchido, o endereço no rodapé da home vira um link que
        abre o mapa. No Google Maps, localize o ponto exato, clique em
        <strong>Compartilhar</strong> e cole aqui o link gerado. Em branco, o
        endereço continua sendo exibido como texto.
    </p>

    <?php
    // isset(): coluna de migration nova - um banco ainda nao migrado nao pode
    // derrubar a tela de Contato com Notice.
    $assinaturaAtual = ($contato !== null && isset($contato['nome_organizador_assinatura']))
        ? (string) $contato['nome_organizador_assinatura']
        : '';
    ?>
    <label>Nome do organizador para assinatura dos e-mails:
        <input type="text" name="nome_organizador_assinatura" value="<?php echo htmlspecialchars($assinaturaAtual, ENT_QUOTES, 'UTF-8'); ?>" maxlength="150" size="60">
    </label>
    <p style="color:#555;font-size:0.9em;">
        Assina os e-mails automáticos (recuperação de senha e liberação de acesso),
        acima do e-mail e do telefone informados acima. Ex.:
        <em>Organização do Prêmio de Inovação - TJRR</em>. Em branco, os e-mails
        saem apenas com os canais de contato preenchidos.
    </p>

    <fieldset>
        <legend>Texto institucional (exibido no rodapé da home)</legend>
        <?php
        $nome = 'texto_institucional';
        $valor = $contato !== null ? (string) $contato['texto_institucional'] : '';
        $rotulo = null;
        include __DIR__ . '/../_editor_rico.php';
        ?>
    </fieldset>

    <fieldset>
        <legend>Redes sociais (deixe em branco as que não se aplicam)</legend>
        <?php $redesAtuais = $contato !== null ? $contato['redes_sociais'] : []; ?>
        <?php foreach (\App\Repositories\ContatoConcursoRepository::REDES_SUPORTADAS as $rede): ?>
            <label><?php echo htmlspecialchars(\App\Repositories\ContatoConcursoRepository::REDES_ROTULOS[$rede], ENT_QUOTES, 'UTF-8'); ?>:
                <input type="text" name="rede_<?php echo $rede; ?>" value="<?php echo htmlspecialchars(isset($redesAtuais[$rede]) ? $redesAtuais[$rede] : '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://...">
            </label><br>
        <?php endforeach; ?>
    </fieldset>

    <label>
        <input type="checkbox" name="formulario_contato_ativo" value="1" <?php echo ($contato !== null && $contato['formulario_contato_ativo']) ? 'checked' : ''; ?>>
        Exibir formulário de contato nativo na home (reduz dependência de links externos)
    </label>

    <div class="form-acoes">
        <a href="<?php echo url('configuracoes/index'); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>
