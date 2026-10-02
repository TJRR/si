<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 58: credenciamento no local (dinâmica de pontos v2, seção 2). O
 * participante lê um código fixo impresso nas paredes do auditório e no
 * balcão, no mesmo leitor de "Confirmar presença". Os pontos vêm de um
 * bônus do tipo "Confirmar o credenciamento no local", cadastrado em Bônus.
 */
$valorDataHora = function ($valor) {
    return !empty($valor) ? date('Y-m-d\TH:i', strtotime($valor)) : '';
};
?>
<div class="pagina-titulo-acoes">
    <h1>Credenciamento no local: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <?php if (!empty($config['codigo'])): ?>
            <a href="<?php echo url('gamificacao/credenciamentoCartaz/' . (int) $evento['id']); ?>" class="btn-acao" target="_blank" rel="noopener">Imprimir o cartaz</a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$podeEditar): ?>
    <p class="status-pill laranja">Somente leitura: só o Administrador altera o credenciamento.</p>
<?php endif; ?>

<p style="color:#555;font-size:0.9em;">
    É a chegada da pessoa ao evento, lida no próprio celular: diferente do "Modo de credenciamento" de Dados
    Gerais, que trata da homologação da inscrição. Só vale na presença física: não há código para quem acompanha
    pela internet. Cada pessoa se credencia uma vez. Para pontuar, cadastre em Bônus um bônus do tipo
    "Confirmar o credenciamento no local", com os pontos que ele vale.
</p>

<form method="post" action="<?php echo url('gamificacao/credenciamento/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
    <fieldset <?php echo $podeEditar ? '' : 'disabled'; ?>>
        <legend>Leitura do código</legend>

        <label>
            <input type="checkbox" name="ativo" value="1" <?php echo (int) $config['ativo'] === 1 ? 'checked' : ''; ?>>
            Aceitar o credenciamento no local
        </label>

        <label>A partir de
            <input type="datetime-local" name="leitura_inicio" value="<?php echo htmlspecialchars($valorDataHora($config['leitura_inicio']), ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <label>Até
            <input type="datetime-local" name="leitura_fim" value="<?php echo htmlspecialchars($valorDataHora($config['leitura_fim']), ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <p style="color:#555;font-size:0.9em;">
            Fora deste período a leitura é recusada, com a mensagem do período. Com os dois campos em branco, valem os
            dias do evento inteiros
            (<?php echo htmlspecialchars(formatarData($evento['data_inicio']), ENT_QUOTES, 'UTF-8'); ?> a
            <?php echo htmlspecialchars(formatarData($evento['data_fim']), ENT_QUOTES, 'UTF-8'); ?>).
        </p>

        <?php if (!empty($config['codigo'])): ?>
            <p>
                <strong>Código do credenciamento:</strong>
                <span style="font-family:'Courier New',Courier,monospace;font-size:1.2em;letter-spacing:2px;"><?php echo htmlspecialchars($config['codigo'], ENT_QUOTES, 'UTF-8'); ?></span>
            </p>
            <p style="color:#555;font-size:0.9em;">O código não muda depois de gerado, porque já pode estar impresso.</p>
        <?php else: ?>
            <p style="color:#555;font-size:0.9em;">O código é gerado quando o credenciamento é ligado pela primeira vez.</p>
        <?php endif; ?>
    </fieldset>

    <?php if ($podeEditar): ?>
    <div class="form-acoes">
        <button type="submit">Salvar</button>
    </div>
    <?php endif; ?>
</form>

<h2>Quem se credenciou</h2>

<?php
$parametrosFiltro = array_filter($filtros, function ($valor) {
    return $valor !== '' && $valor !== null;
});
?>
<div class="filtros-barra-wrapper">
    <form method="get" action="<?php echo config('base_path'); ?>/index.php" class="filtros-barra">
        <input type="hidden" name="r" value="gamificacao/credenciamento/<?php echo (int) $evento['id']; ?>">
        <label class="filtro-busca">Busca:
            <input type="text" name="busca" placeholder="Nome ou correio eletrônico" value="<?php echo htmlspecialchars($filtros['busca'], ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <label>De:
            <input type="date" name="data_inicio" value="<?php echo htmlspecialchars($filtros['data_inicio'], ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <label>Até:
            <input type="date" name="data_fim" value="<?php echo htmlspecialchars($filtros['data_fim'], ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <div class="filtros-barra-acoes">
            <button type="submit" class="btn-icone" title="Filtrar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                </svg>
            </button>
            <a href="<?php echo url('gamificacao/credenciamento/' . (int) $evento['id']); ?>" class="btn-icone" title="Limpar filtros">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="1 4 1 10 7 10"></polyline>
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                </svg>
            </a>
        </div>
    </form>
</div>

<div class="auditoria-resumo">
    <span>
        <?php echo count($credenciados); ?>
        <?php echo count($credenciados) === 1 ? 'pessoa encontrada' : 'pessoas encontradas'; ?>
    </span>
    <?php if (!empty($credenciados)): ?>
        <a href="<?php echo htmlspecialchars(url('gamificacao/credenciamentoExportar/' . (int) $evento['id']) . ($parametrosFiltro !== [] ? '&' . http_build_query($parametrosFiltro) : ''), ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Exportar a lista filtrada">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
        </a>
    <?php endif; ?>
</div>

<?php if (empty($credenciados)): ?>
    <p>Ninguém se credenciou no local<?php echo $parametrosFiltro !== [] ? ' com estes filtros' : ' até o momento'; ?>.</p>
<?php else: ?>
    <?php if ($podeEditar): ?>
        <?php /* Formulário de lote vazio e fora da tabela: as caixas de cada linha
        se ligam a ele pelo atributo "form=", porque HTML não aceita formulário
        dentro de formulário. */ ?>
        <form method="post" id="form-acoes-em-massa" action="<?php echo url('gamificacao/credenciamentoRemover/' . (int) $evento['id']); ?>"
              onsubmit="return confirm('Remover o credenciamento das pessoas selecionadas? Elas perdem o bônus de credenciamento e podem se credenciar de novo.');"><?= campoCsrf() ?>
            <input type="hidden" name="evento_id" value="<?php echo (int) $evento['id']; ?>">
        </form>
    <?php endif; ?>

    <div class="tabela-scroll">
        <table border="1" cellpadding="6">
            <tr>
                <?php if ($podeEditar): ?>
                    <th><input type="checkbox" id="marcar-todos"></th>
                <?php endif; ?>
                <th>Nome</th><th>Correio eletrônico</th><th>Credenciado em</th>
            </tr>
            <?php foreach ($credenciados as $credenciado): ?>
                <tr>
                    <?php if ($podeEditar): ?>
                        <td><input type="checkbox" class="marcar-linha" name="credenciamento_ids[]" value="<?php echo (int) $credenciado['id']; ?>" form="form-acoes-em-massa"></td>
                    <?php endif; ?>
                    <td><?php echo htmlspecialchars($credenciado['participante_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($credenciado['participante_email'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars(formatarDataHora($credenciado['credenciado_em']), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <?php if ($podeEditar): ?>
        <p>Com as selecionadas:
            <span class="acoes-icones">
                <button type="submit" form="form-acoes-em-massa" class="btn-icone" title="Remover o credenciamento">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                        <path d="M10 11v6M14 11v6"></path>
                    </svg>
                </button>
            </span>
        </p>
    <?php endif; ?>
<?php endif; ?>
