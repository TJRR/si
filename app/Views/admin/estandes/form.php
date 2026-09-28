<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php $ehNovo = $estande === null || empty($estande['id']); ?>
<div class="pagina-titulo-acoes">
    <h1><?php echo $ehNovo ? 'Novo estande' : 'Estande: ' . htmlspecialchars($estande['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <?php if (!$ehNovo): ?>
            <a href="<?php echo url('estandes/codigo/' . (int) $estande['id']); ?>" class="btn-acao" target="_blank" rel="noopener">Imprimir cartaz</a>
        <?php endif; ?>
        <a href="<?php echo url('estandes/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<?php if (!empty($erro)): ?>
    <p class="flash-mensagem erro"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php if (!$podeEditar): ?>
    <p class="status-pill laranja">Somente leitura: só o Administrador altera estandes.</p>
<?php endif; ?>

<?php if (!$ehNovo): ?>
    <section class="admin-card">
        <p><strong>Código de visita:</strong> <span style="font-family:'Courier New',Courier,monospace;font-size:1.2em;letter-spacing:2px;"><?php echo htmlspecialchars($estande['codigo_estande'], ENT_QUOTES, 'UTF-8'); ?></span></p>
        <p style="color:#555;font-size:0.9em;">Gerado pelo sistema na criação do estande e fixo: não muda nunca. É o código impresso no cartaz (QR e texto) que o participante lê no aplicativo do evento para registrar a visita.</p>
    </section>
<?php endif; ?>

<form method="post" action="<?php echo $ehNovo ? url('estandes/novo/' . (int) $evento['id']) : url('estandes/editar/' . (int) $estande['id']); ?>" enctype="multipart/form-data"><?= campoCsrf() ?>
    <fieldset <?php echo $podeEditar ? '' : 'disabled'; ?>>
        <legend>Dados do estande</legend>

        <label>Nome do estande: *
            <input type="text" name="nome" maxlength="150" required value="<?php echo htmlspecialchars($estande !== null ? (string) $estande['nome'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </label>

        <label>Categoria: *
            <select name="categoria">
                <?php foreach (\App\Repositories\EstandeRepository::CATEGORIAS as $valorOpcao => $rotuloOpcao): ?>
                    <option value="<?php echo $valorOpcao; ?>" <?php echo ($estande !== null ? $estande['categoria'] : 'expositor') === $valorOpcao ? 'selected' : ''; ?>><?php echo $rotuloOpcao; ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Pontos por visita: *
            <input type="number" name="pontos_visita" min="0" max="65535" step="1" required value="<?php echo htmlspecialchars($estande !== null ? (string) $estande['pontos_visita'] : '0', ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <p style="color:#555;font-size:0.9em;">Quantos pontos o participante ganha ao registrar a visita a este estande. O valor vale no momento da visita: mudar depois não altera os pontos de quem já visitou.</p>

        <fieldset>
            <legend>Descrição (opcional; aparece no aplicativo e na página do evento)</legend>
            <?php if ($podeEditar): ?>
                <?php
                $nome = 'descricao_html';
                $valor = $estande !== null ? (string) $estande['descricao_html'] : '';
                $rotulo = null;
                include __DIR__ . '/../_editor_rico.php';
                ?>
            <?php else: ?>
                <div><?php echo $estande !== null ? sanitizarHtmlRico((string) $estande['descricao_html']) : ''; ?></div>
            <?php endif; ?>
        </fieldset>

        <fieldset>
            <legend>Logotipo (opcional)</legend>
            <?php if ($estande !== null && !empty($estande['logotipo_path'])): ?>
                <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $estande['logotipo_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="" style="max-width:240px;max-height:120px;display:block;margin:.5rem 0;background:#fff;">
            <?php endif; ?>
            <label>Imagem (JPG, PNG, WEBP ou GIF, até 4 MB; o sistema reduz para no máximo 600 por 300 pixels):
                <input type="file" name="logotipo" accept="image/*">
            </label>
            <label>Texto alternativo do logotipo (obrigatório se houver logotipo; descreve a imagem para quem usa leitor de tela):
                <input type="text" name="logotipo_alt" maxlength="255" value="<?php echo htmlspecialchars($estande !== null ? (string) $estande['logotipo_alt'] : '', ENT_QUOTES, 'UTF-8'); ?>">
            </label>
        </fieldset>

        <label>
            <input type="checkbox" name="ativo" value="1" <?php echo ($estande === null || !empty($estande['ativo'])) ? 'checked' : ''; ?>>
            Ativo (aparece no aplicativo e na página e recebe visitas)
        </label>
    </fieldset>

    <?php if ($podeEditar): ?>
    <div class="form-acoes">
        <a href="<?php echo url('estandes/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
    <?php endif; ?>
</form>

<?php if (!$ehNovo): ?>
    <section class="admin-card">
        <h2>Representante</h2>
        <p style="color:#555;font-size:0.9em;">Pessoa do expositor ou do patrocinador que acompanha este estande pelo sistema: ela atualiza nome, descrição e logotipo, baixa o cartaz e vê quantas visitas o estande recebeu. Código, pontos, categoria e situação continuam só com o Administrador. Uma pessoa representa no máximo um estande por evento.</p>

        <?php if ($representante === null): ?>
            <p>Nenhum representante convidado.</p>
            <?php if ($podeEditar): ?>
                <form method="post" action="<?php echo url('estandes/convidarRepresentante'); ?>"><?= campoCsrf() ?>
                    <input type="hidden" name="estande_id" value="<?php echo (int) $estande['id']; ?>">
                    <label>Nome: * <input type="text" name="nome" maxlength="150" required></label>
                    <label>E-mail: * <input type="email" name="email" maxlength="150" required></label>
                    <p style="color:#555;font-size:0.9em;">Sem conta no sistema, a pessoa recebe por e-mail o endereço para definir a senha. Com conta, recebe o aviso e entra com o acesso de sempre. O texto do convite é editável em Estandes, Configurações.</p>
                    <button type="submit">Convidar representante</button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <p>
                <strong><?php echo htmlspecialchars($representante['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                (<?php echo htmlspecialchars($representante['email'], ENT_QUOTES, 'UTF-8'); ?>)
                <br>
                <?php if (empty($representante['usuario_ativo'])): ?>
                    <span class="status-pill vermelho">Conta suspensa</span>
                <?php elseif (!empty($representante['convite_pendente'])): ?>
                    <span class="status-pill laranja">Convite pendente: a senha ainda não foi definida</span>
                <?php else: ?>
                    <span class="status-pill verde">Acesso ativo</span>
                <?php endif; ?>
            </p>

            <?php if ($podeEditar): ?>
                <details>
                    <summary>Substituir representante</summary>
                    <form method="post" action="<?php echo url('estandes/substituirRepresentante'); ?>" onsubmit="return confirm('O acesso do representante atual a este estande será encerrado. Continuar?');"><?= campoCsrf() ?>
                        <input type="hidden" name="estande_id" value="<?php echo (int) $estande['id']; ?>">
                        <label>Nome do novo representante: * <input type="text" name="nome" maxlength="150" required></label>
                        <label>E-mail do novo representante: * <input type="email" name="email" maxlength="150" required></label>
                        <p style="color:#555;font-size:0.9em;">O acesso de <?php echo htmlspecialchars($representante['nome'], ENT_QUOTES, 'UTF-8'); ?> a este estande será encerrado. Se ele não representar nenhum outro estande, perde também o perfil de representante.</p>
                        <button type="submit">Substituir</button>
                    </form>
                </details>

                <form method="post" action="<?php echo url('estandes/removerRepresentante'); ?>" onsubmit="return confirm('Remover o representante? O acesso dele a este estande será encerrado.');" style="margin-top:.75rem;"><?= campoCsrf() ?>
                    <input type="hidden" name="estande_id" value="<?php echo (int) $estande['id']; ?>">
                    <button type="submit" class="btn-voltar">Remover representante</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </section>
<?php endif; ?>
