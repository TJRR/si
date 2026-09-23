<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1><?php echo $trilha === null ? 'Nova trilha' : 'Editar trilha'; ?>: <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php $somenteLeitura = !\App\Core\Auth::possuiPerfil('administrador'); ?>
<form method="post" action="<?php echo $trilha === null ? url('trilhas/novo/' . (int) $concurso['id']) : url('trilhas/editar/' . (int) $trilha['id']); ?>"><?= campoCsrf() ?>
    <label>Nome:
        <input type="text" name="nome" required value="<?php echo htmlspecialchars($trilha !== null ? $trilha['nome'] : '', ENT_QUOTES, 'UTF-8'); ?>" <?php echo $somenteLeitura ? 'disabled' : ''; ?>>
    </label><br>

    <label>Descrição:<br>
        <textarea name="descricao" rows="4" cols="50" <?php echo $somenteLeitura ? 'disabled' : ''; ?>><?php echo htmlspecialchars($trilha !== null ? (string) $trilha['descricao'] : '', ENT_QUOTES, 'UTF-8'); ?></textarea>
    </label><br>

    <label>Ordem:
        <input type="number" name="ordem" value="<?php echo $trilha !== null ? (int) $trilha['ordem'] : 0; ?>" <?php echo $somenteLeitura ? 'disabled' : ''; ?>>
    </label><br>

    <label>Mínimo de integrantes homologados para a equipe contar como homologada:
        <input type="number" name="minimo_integrantes_homologados" min="1" value="<?php echo $trilha !== null ? (int) $trilha['minimo_integrantes_homologados'] : 1; ?>" <?php echo $somenteLeitura ? 'disabled' : ''; ?>>
    </label>
    <p><small>Usado na página pública de equipes homologadas: defina aqui o critério de cada edital, sem depender de alteração de código.</small></p><br>

    <?php
    // Fase 51: divulgação pública do resultado FINAL desta trilha. Nasce
    // oculto: nada muda para quem já usa até o Admin escolher outra opção.
    $visibilidadeResultado = $trilha !== null && isset($trilha['visibilidade_publica_resultado'])
        ? (string) $trilha['visibilidade_publica_resultado']
        : 'oculto';
    ?>
    <label>Resultado final desta trilha na página pública:
        <select name="visibilidade_publica_resultado" <?php echo $somenteLeitura ? 'disabled' : ''; ?>>
            <option value="oculto" <?php echo $visibilidadeResultado === 'oculto' ? 'selected' : ''; ?>>Oculto</option>
            <option value="apenas_destaques" <?php echo $visibilidadeResultado === 'apenas_destaques' ? 'selected' : ''; ?>>Só as colocações com destaque cadastrado</option>
            <option value="ranking_completo" <?php echo $visibilidadeResultado === 'ranking_completo' ? 'selected' : ''; ?>>Classificação completa, com Nota Final</option>
        </select>
    </label>
    <p><small>Só vale depois que o resultado final estiver publicado em Apuração. O resumo e a imagem de cada case são os do "Destaque público", em Resultado da trilha.</small></p><br>

    <label>
        <input type="checkbox" name="ativo" value="1" <?php echo ($trilha === null || $trilha['ativo']) ? 'checked' : ''; ?> <?php echo $somenteLeitura ? 'disabled' : ''; ?>>
        Ativa
    </label><br>

    <div class="form-acoes">
        <a href="<?php echo url('trilhas/index/' . (int) $concurso['id']); ?>" class="btn-voltar">Voltar</a>
        <?php if (!$somenteLeitura): ?>
        <button type="submit">Salvar</button>
        <?php endif; ?>
    </div>
</form>
