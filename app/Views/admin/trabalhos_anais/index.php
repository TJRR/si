<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
$esc = function ($texto) {
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
};

$fonte = $valores !== null ? $valores : ($anais !== null ? $anais : []);
$titulo = isset($fonte['titulo']) ? (string) $fonte['titulo'] : '';
$identificadorTipo = isset($fonte['identificador_tipo']) ? (string) $fonte['identificador_tipo'] : 'nenhum';
$identificador = isset($fonte['identificador']) ? (string) $fonte['identificador'] : '';
$descricao = isset($fonte['descricao']) ? (string) $fonte['descricao'] : '';
$mensagemHtml = isset($fonte['mensagem_publicacao_html']) ? (string) $fonte['mensagem_publicacao_html'] : '';
$tituloBloqueado = $anais !== null && !empty($anais['documento_titulo']);
$idPublicada = $anais !== null && !empty($anais['versao_publicada_id']) ? (int) $anais['versao_publicada_id'] : 0;
$estaPublicado = $idPublicada > 0;

$numeroPublicada = null;
foreach ($versoes as $versao) {
    if ((int) $versao['id'] === $idPublicada) {
        $numeroPublicada = (int) $versao['numero'];
    }
}

$candidatas = [];
foreach ($versoes as $versao) {
    if ((int) $versao['id'] !== $idPublicada) {
        $candidatas[] = $versao;
    }
}
?>
<div class="pagina-titulo-acoes">
    <h1>Anais: <?php echo $esc($evento['nome']); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('trabalhos/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<p>
    <?php if ($estaPublicado): ?>
        <span class="selo-situacao verde">Publicado</span>
        Versão <?php echo (int) $numeroPublicada; ?> publicada em <?php echo $esc(formatarDataHora($anais['publicado_em'])); ?>. O botão Anais aparece no aplicativo do participante, e o documento pode ser ligado a um botão da página pública do evento (Seções da página, Cronograma).
    <?php else: ?>
        <span class="selo-situacao laranja">Não publicado</span>
        Os Anais ainda não aparecem para os participantes.
    <?php endif; ?>
</p>

<p style="color:#555;font-size:0.9em;">Os Anais são um único arquivo PDF, com tudo dentro: capa, folha de rosto, ficha catalográfica, expediente, comissões, sumário e os trabalhos. A versão pode ser montada fora do sistema e enviada aqui, ou montada pelo próprio sistema na aba Montagem dos Anais; nos dois casos ela aparece na lista de versões abaixo, para você conferir e publicar. Só é possível publicar depois de publicar o resultado de Trabalhos.</p>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo $esc($erro); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url('trabalhoAnais/index/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
    <input type="hidden" name="acao" value="salvar_dados">
    <fieldset>
        <legend>Identificação</legend>
        <label>Título dos Anais:
            <input type="text" name="titulo" required maxlength="<?php echo (int) \App\Services\EventoAnaisService::TITULO_MAXIMO; ?>" value="<?php echo $esc($titulo); ?>" <?php echo $tituloBloqueado ? 'readonly' : ''; ?>>
        </label>
        <?php if ($tituloBloqueado): ?>
            <p style="color:#555;font-size:0.9em;">O título não muda depois da primeira publicação, para as versões continuarem juntas no mesmo documento do evento.</p>
        <?php endif; ?>

        <label>Identificador editorial:
            <select name="identificador_tipo">
                <option value="nenhum" <?php echo $identificadorTipo === 'nenhum' ? 'selected' : ''; ?>>Sem identificador</option>
                <option value="issn" <?php echo $identificadorTipo === 'issn' ? 'selected' : ''; ?>>ISSN</option>
                <option value="isbn" <?php echo $identificadorTipo === 'isbn' ? 'selected' : ''; ?>>ISBN</option>
            </select>
        </label>
        <label>Número do identificador:
            <input type="text" name="identificador" maxlength="30" placeholder="ISSN: 0000-0000. ISBN: 10 ou 13 dígitos" value="<?php echo $esc($identificador); ?>">
        </label>

        <label>Descrição curta (aparece junto do botão no aplicativo):
            <textarea name="descricao" rows="3" maxlength="<?php echo (int) \App\Services\EventoAnaisService::DESCRICAO_MAXIMA; ?>"><?php echo $esc($descricao); ?></textarea>
        </label>

        <p>Texto do e-mail de aviso aos autores (aparece no fim da mensagem; em branco, vale um texto padrão):</p>
        <?php
        $nome = 'mensagem_publicacao_html';
        $valor = $mensagemHtml;
        $rotulo = null;
        include __DIR__ . '/../_editor_rico.php';
        ?>
    </fieldset>
    <div class="form-acoes">
        <button type="submit">Salvar identificação</button>
    </div>
</form>

<h2>Enviar uma versão</h2>
<p style="color:#555;font-size:0.9em;">Cada envio vira uma nova versão numerada. O arquivo fica guardado em área privada até você publicá-lo. Tamanho máximo aceito pelo servidor: <?php echo $esc($limiteMB); ?> MB.</p>
<form method="post" action="<?php echo url('trabalhoAnais/enviarVersao/' . (int) $evento['id']); ?>" enctype="multipart/form-data"><?= campoCsrf() ?>
    <label>Arquivo PDF dos Anais:
        <input type="file" name="arquivo" accept=".pdf,application/pdf" required>
    </label>
    <label>Observação (opcional, só para a equipe):
        <input type="text" name="observacao" maxlength="<?php echo (int) \App\Services\EventoAnaisService::OBSERVACAO_MAXIMA; ?>" placeholder="Ex.: revisão final do sumário">
    </label>
    <div class="form-acoes">
        <button type="submit">Enviar versão</button>
    </div>
</form>

<h2>Versões enviadas</h2>
<?php if (empty($versoes)): ?>
    <p>Nenhuma versão enviada ainda.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Versão</th><th>Arquivo</th><th>Tamanho</th><th>Enviada</th><th>Situação</th><th>Ações</th></tr>
        <?php foreach ($versoes as $versao): ?>
        <tr>
            <td><?php echo (int) $versao['numero']; ?></td>
            <td>
                <?php echo $esc($versao['nome_original']); ?>
                <?php if (!empty($versao['observacao'])): ?><br><small><?php echo $esc($versao['observacao']); ?></small><?php endif; ?>
            </td>
            <td><?php echo $esc(number_format(((float) $versao['tamanho_bytes']) / 1048576, 2, ',', '.')); ?> MB</td>
            <td><?php echo $esc(formatarDataHora($versao['enviado_em'])); ?><?php echo !empty($versao['enviado_por_nome']) ? '<br><small>por ' . $esc($versao['enviado_por_nome']) . '</small>' : ''; ?></td>
            <td>
                <?php if ((int) $versao['id'] === $idPublicada): ?>
                    <span class="status-pill verde">Publicada agora</span>
                <?php elseif ($versao['publicado_em'] !== null): ?>
                    <span class="status-pill">Já publicada em <?php echo $esc(formatarDataHora($versao['publicado_em'])); ?></span>
                <?php else: ?>
                    <span class="status-pill laranja">Não publicada</span>
                <?php endif; ?>
            </td>
            <td>
                <div class="acoes-icones">
                    <a href="<?php echo url('trabalhoAnais/baixar/' . (int) $evento['id'] . '/' . (int) $versao['id']); ?>" class="btn-icone" title="Abrir o PDF desta versão" target="_blank" rel="noopener">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                    </a>
                    <?php if ($versao['publicado_em'] === null && $versao['documento_id'] === null): ?>
                        <form method="post" action="<?php echo url('trabalhoAnais/removerVersao/' . (int) $evento['id']); ?>" onsubmit="return confirm('Remover esta versão? O arquivo enviado é apagado e não pode ser recuperado.');"><?= campoCsrf() ?>
                            <input type="hidden" name="id" value="<?php echo (int) $versao['id']; ?>">
                            <button type="submit" class="btn-icone" title="Remover esta versão (só versões nunca publicadas)">
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
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<h2>Publicar</h2>
<p style="color:#555;font-size:0.9em;"><strong>Atenção:</strong> o PDF publicado fica acessível a qualquer pessoa que tenha o endereço. Antes de publicar, confira que o arquivo não traz CPF, telefone pessoal, endereço residencial ou qualquer dado que não deva ser público. Despublicar esconde o botão da página e do aplicativo, mas quem já tem o endereço direto continua conseguindo abri-lo.</p>

<?php if ($estaPublicado && !empty($enderecoPublico)): ?>
    <p><strong>Endereço do PDF publicado:</strong> <a href="<?php echo $esc($enderecoPublico); ?>" target="_blank" rel="noopener"><?php echo $esc($enderecoPublico); ?></a></p>
    <?php if (!empty($publicado['rotulo_identificador'])): ?>
        <p><strong>Identificador:</strong> <?php echo $esc($publicado['rotulo_identificador']); ?></p>
    <?php endif; ?>
<?php endif; ?>

<?php if ($impedimento !== null): ?>
    <p><span class="selo-situacao laranja">Publicação indisponível</span> <?php echo $esc($impedimento); ?></p>
<?php elseif (empty($candidatas)): ?>
    <p><?php echo $estaPublicado ? 'Não há outra versão para publicar. Envie uma nova versão acima para substituir a publicada.' : 'Envie uma versão acima para poder publicar.'; ?></p>
<?php else: ?>
    <?php
    $textoPublicar = 'Publicar a versão escolhida dos Anais agora? O PDF passa a ficar acessível a qualquer pessoa com o endereço.'
        . ($estaPublicado ? ' A versão publicada hoje fica arquivada.' : '')
        . ' Se a caixa de aviso estiver marcada, os autores dos trabalhos incluídos serão avisados por e-mail e no aplicativo.';
    // Segunda barreira contra o duplo clique: depois da confirmação o botão
    // é desabilitado. A primeira é a trava de linha do repositório.
    $aoEnviar = 'if (!confirm(' . json_encode($textoPublicar) . ')) { return false; } this.querySelector(\'button\').disabled = true;';
    ?>
    <form method="post" action="<?php echo url('trabalhoAnais/publicar/' . (int) $evento['id']); ?>" onsubmit="<?php echo $esc($aoEnviar); ?>"><?= campoCsrf() ?>
        <label>Versão a publicar:
            <select name="id" required>
                <?php foreach ($candidatas as $versao): ?>
                    <option value="<?php echo (int) $versao['id']; ?>">Versão <?php echo (int) $versao['numero']; ?>: <?php echo $esc($versao['nome_original']); ?> (enviada em <?php echo $esc(formatarDataHora($versao['enviado_em'])); ?>)</option>
                <?php endforeach; ?>
            </select>
        </label>
        <label style="display:block;">
            <input type="checkbox" name="avisar_autores" value="1" <?php echo $primeiraPublicacao ? 'checked' : ''; ?>>
            Avisar os autores dos trabalhos incluídos (sino do aplicativo e e-mail). Marque na primeira publicação; nas versões seguintes, só se fizer sentido reavisar.
        </label>
        <?php if (!$primeiraPublicacao): ?>
            <fieldset>
                <legend>Quem recebe o aviso, se marcado acima</legend>
                <label style="display:block;">
                    <input type="radio" name="modo_aviso" value="novos" checked>
                    Só os autores de trabalhos que ainda não foram avisados
                </label>
                <label style="display:block;">
                    <input type="radio" name="modo_aviso" value="todos">
                    Todos os autores dos trabalhos incluídos
                </label>
            </fieldset>
        <?php endif; ?>
        <div class="form-acoes">
            <button type="submit">Publicar</button>
        </div>
    </form>
<?php endif; ?>

<?php if ($estaPublicado): ?>
    <form method="post" action="<?php echo url('trabalhoAnais/despublicar/' . (int) $evento['id']); ?>" onsubmit="<?php echo $esc('if (!confirm(' . json_encode('Despublicar os Anais? O botão some da página do evento e do aplicativo. As versões continuam guardadas.') . ')) { return false; } this.querySelector(\'button\').disabled = true;'); ?>"><?= campoCsrf() ?>
        <div class="form-acoes">
            <button type="submit">Despublicar os Anais</button>
        </div>
    </form>
<?php endif; ?>
