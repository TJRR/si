<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1><?php echo $tema === null ? 'Novo tema' : 'Editar tema'; ?> — <?php echo htmlspecialchars($trilha['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php $somenteLeitura = !\App\Core\Auth::possuiPerfil('administrador'); $desabilitado = $somenteLeitura ? 'disabled' : ''; ?>
<form method="post" action="<?php echo $tema === null ? url('temas/novo/' . (int) $trilha['id']) : url('temas/editar/' . (int) $tema['id']); ?>">
    <label>Nome:
        <input type="text" name="nome" required value="<?php echo htmlspecialchars($tema !== null ? $tema['nome'] : '', ENT_QUOTES, 'UTF-8'); ?>" <?php echo $desabilitado; ?>>
    </label><br>

    <label>Descrição longa:<br>
        <textarea name="descricao_longa" rows="6" cols="60" <?php echo $desabilitado; ?>><?php echo htmlspecialchars($tema !== null ? (string) $tema['descricao_longa'] : '', ENT_QUOTES, 'UTF-8'); ?></textarea>
    </label><br>

    <label>Ícone temático (opcional, exibido na grade de Desafios da home):
        <select name="icone" <?php echo $desabilitado; ?>>
            <option value="">— Nenhum —</option>
            <?php foreach (\App\Repositories\TemaRepository::ICONES_DISPONIVEIS as $valorOpcao => $rotuloOpcao): ?>
                <option value="<?php echo $valorOpcao; ?>" <?php echo ($tema !== null && $tema['icone'] === $valorOpcao) ? 'selected' : ''; ?>><?php echo $rotuloOpcao; ?></option>
            <?php endforeach; ?>
        </select>
    </label><br>

    <label>Ordem de exibição na home (menor aparece primeiro):
        <input type="number" name="ordem" value="<?php echo $tema !== null ? (int) $tema['ordem'] : 0; ?>" <?php echo $desabilitado; ?>>
    </label><br>

    <label>
        <input type="checkbox" name="ativo" value="1" <?php echo ($tema === null || $tema['ativo']) ? 'checked' : ''; ?> <?php echo $desabilitado; ?>>
        Ativo
    </label><br>

    <div class="form-acoes">
        <a href="<?php echo url('temas/index/' . (int) $trilha['id']); ?>" class="btn-voltar">Voltar</a>
        <?php if (!$somenteLeitura): ?>
        <button type="submit">Salvar</button>
        <?php endif; ?>
    </div>
</form>
