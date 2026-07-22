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

<form method="post" action="<?php echo url('contatosConcurso/index'); ?>">
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
            <label><?php echo ucfirst($rede); ?>:
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
