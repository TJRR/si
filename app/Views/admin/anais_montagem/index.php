<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
$esc = function ($texto) {
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
};

$eventoId = (int) $evento['id'];
$dadosMontagem = $montagem !== null ? $montagem : [];
$campoMontagem = function ($chave) use ($dadosMontagem) {
    return isset($dadosMontagem[$chave]) && $dadosMontagem[$chave] !== null ? (string) $dadosMontagem[$chave] : '';
};
$rotuloPaginas = function ($paginas) {
    return (int) $paginas === 1 ? '1 página' : (int) $paginas . ' páginas';
};

$svgBaixar = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>';
$svgEditar = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>';
$svgRemover = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>';

// Bloco 1: valores do formulario (os digitados, quando voltou por erro).
$prazoGravado = $campoMontagem('prazo_pdf_final');

if ($valoresPrazo !== null) {
    $prazoCampo = (string) $valoresPrazo['prazo_digitado'];
    $instrucoesCampo = (string) $valoresPrazo['instrucoes_pdf_final_html'];
    $avisoCampo = (string) $valoresPrazo['mensagem_aviso_pdf_final_html'];
    $avisarMarcado = !empty($valoresPrazo['avisar_autores']);
} else {
    $prazoCampo = $prazoGravado !== '' ? date('Y-m-d\TH:i', strtotime($prazoGravado)) : '';
    $instrucoesCampo = sanitizarHtmlRico($campoMontagem('instrucoes_pdf_final_html'));
    $avisoCampo = sanitizarHtmlRico($campoMontagem('mensagem_aviso_pdf_final_html'));
    $avisarMarcado = $prazoGravado === '';
}

// Bloco 2
if ($valoresEditorial !== null) {
    $editorial = [];

    foreach ($valoresEditorial as $chave => $conteudo) {
        $editorial[$chave] = (string) $conteudo;
    }
} else {
    $editorial = [
        'subtitulo' => $campoMontagem('subtitulo'),
        'local_ano' => $campoMontagem('local_ano'),
        'organizadores_html' => sanitizarHtmlRico($campoMontagem('organizadores_html')),
        'ficha_catalografica_html' => sanitizarHtmlRico($campoMontagem('ficha_catalografica_html')),
        'expediente_html' => sanitizarHtmlRico($campoMontagem('expediente_html')),
        'apresentacao_html' => sanitizarHtmlRico($campoMontagem('apresentacao_html')),
    ];
}

$temCapa = $campoMontagem('capa_path') !== '';
$tituloAnais = $anais !== null ? trim((string) $anais['titulo']) : '';
$identificadorAnais = $anais !== null ? \App\Repositories\EventoAnaisRepository::rotuloIdentificador($anais) : '';

// Bloco 4
$pendentes = 0;

foreach ($trabalhos as $linhaTrabalho) {
    if ($linhaTrabalho['arquivo'] === null) {
        $pendentes++;
    }
}

// Bloco 5
$situacoesPedido = [
    'pendente' => ['rotulo' => 'Na fila', 'cor' => 'laranja'],
    'processando' => ['rotulo' => 'Em geração', 'cor' => 'azul'],
    'concluida' => ['rotulo' => 'Concluída', 'cor' => 'verde'],
    'falhou' => ['rotulo' => 'Falhou', 'cor' => 'vermelho'],
];
?>
<div class="pagina-titulo-acoes">
    <h1>Montagem dos Anais: <?php echo $esc($evento['nome']); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('trabalhoAnais/index/' . $eventoId); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<p style="color:#555;font-size:0.9em;">Aqui o sistema monta o volume dos Anais sozinho, como alternativa ao envio do PDF pronto na aba Anais. Os autores principais enviam a versão final de cada trabalho pelo aplicativo; você preenche os dados editoriais e as comissões, confere a ordem dos trabalhos e pede a geração. O volume gerado entra como uma nova versão na aba Anais, sem publicar: a publicação continua sendo feita lá.</p>

<fieldset id="bloco-prazo">
    <legend>1. Envio do PDF final pelos autores</legend>
    <p style="color:#555;font-size:0.9em;">O autor principal de cada trabalho que consta nos Anais envia, pelo aplicativo, a versão final do trabalho em PDF até o prazo. O quadro de envio só aparece para os autores depois que o resultado de Trabalhos é publicado. Você acompanha quem já enviou no bloco 4; o sistema não aceita envio em nome do autor.</p>

    <?php if ($erroPrazo !== null): ?>
        <p style="color:red;"><?php echo $esc($erroPrazo); ?></p>
    <?php endif; ?>

    <?php if (!$resultadoPublicado): ?>
        <p><span class="selo-situacao laranja">Resultado não publicado</span> Você já pode definir o prazo e as instruções, mas nenhum autor vê o quadro nem recebe aviso antes da publicação do resultado.</p>
    <?php endif; ?>

    <form method="post" action="<?php echo url('anaisMontagem/salvarPrazo/' . $eventoId); ?>"><?= campoCsrf() ?>
        <label>Prazo para o envio <?php echo $esc(sufixoFusoHorario()); ?>:
            <input type="datetime-local" name="prazo_pdf_final" value="<?php echo $esc($prazoCampo); ?>">
        </label>
        <p style="color:#555;font-size:0.9em;">Em branco, o envio fica fechado. Para dar mais tempo a quem ainda não enviou, basta mudar a data e salvar.</p>

        <p>Instruções para os autores (aparecem no quadro do trabalho, no aplicativo):</p>
        <?php
        $nome = 'instrucoes_pdf_final_html';
        $valor = $instrucoesCampo;
        $rotulo = null;
        include __DIR__ . '/../_editor_rico.php';
        ?>

        <p>Texto do e-mail de aviso (aparece no fim da mensagem; em branco, vale um texto padrão):</p>
        <?php
        $nome = 'mensagem_aviso_pdf_final_html';
        $valor = $avisoCampo;
        $rotulo = null;
        include __DIR__ . '/../_editor_rico.php';
        ?>

        <label style="display:block;">
            <input type="checkbox" name="avisar_autores" value="1" <?php echo $avisarMarcado ? 'checked' : ''; ?>>
            Avisar os autores principais ao salvar (sino do aplicativo e e-mail). Marque ao abrir o prazo e, se quiser, ao prorrogá-lo. Nenhum aviso sai sem prazo preenchido ou antes da publicação do resultado.
        </label>
        <div class="form-acoes">
            <button type="submit">Salvar</button>
        </div>
    </form>
</fieldset>

<fieldset id="bloco-editorial">
    <legend>2. Dados editoriais</legend>
    <?php if ($tituloAnais !== ''): ?>
        <p style="color:#555;font-size:0.9em;">Título dos Anais: <strong><?php echo $esc($tituloAnais); ?></strong><?php echo $identificadorAnais !== '' ? ' (' . $esc($identificadorAnais) . ')' : ''; ?>. O título e o ISSN ou ISBN são editados na <a href="<?php echo url('trabalhoAnais/index/' . $eventoId); ?>">aba Anais</a>.</p>
    <?php else: ?>
        <p><span class="selo-situacao laranja">Título não salvo</span> Salve o título dos Anais na <a href="<?php echo url('trabalhoAnais/index/' . $eventoId); ?>">aba Anais</a> antes de gerar o volume.</p>
    <?php endif; ?>

    <?php if ($erroEditorial !== null): ?>
        <p style="color:red;"><?php echo $esc($erroEditorial); ?></p>
    <?php endif; ?>

    <form method="post" action="<?php echo url('anaisMontagem/salvarEditorial/' . $eventoId); ?>"><?= campoCsrf() ?>
        <label>Subtítulo (opcional):
            <input type="text" name="subtitulo" maxlength="<?php echo (int) \App\Services\EventoAnaisMontagemService::SUBTITULO_MAXIMO; ?>" value="<?php echo $esc($editorial['subtitulo']); ?>">
        </label>
        <label>Local e ano (sai na capa simples e na folha de rosto):
            <input type="text" name="local_ano" maxlength="<?php echo (int) \App\Services\EventoAnaisMontagemService::LOCAL_ANO_MAXIMO; ?>" placeholder="Ex.: Cidade (UF), 2026" value="<?php echo $esc($editorial['local_ano']); ?>">
        </label>

        <p>Organizadores (topo da folha de rosto):</p>
        <?php
        $nome = 'organizadores_html';
        $valor = $editorial['organizadores_html'];
        $rotulo = null;
        include __DIR__ . '/../_editor_rico.php';
        ?>

        <p>Ficha catalográfica (texto fornecido pela biblioteca; sai no verso da folha de rosto, no pé da página, dentro de um quadro):</p>
        <?php
        $nome = 'ficha_catalografica_html';
        $valor = $editorial['ficha_catalografica_html'];
        $rotulo = null;
        include __DIR__ . '/../_editor_rico.php';
        ?>

        <p>Expediente (página própria):</p>
        <?php
        $nome = 'expediente_html';
        $valor = $editorial['expediente_html'];
        $rotulo = null;
        include __DIR__ . '/../_editor_rico.php';
        ?>

        <p>Apresentação (opcional, página própria, antes do sumário):</p>
        <?php
        $nome = 'apresentacao_html';
        $valor = $editorial['apresentacao_html'];
        $rotulo = null;
        include __DIR__ . '/../_editor_rico.php';
        ?>

        <p style="color:#555;font-size:0.9em;">Campo em branco não entra no volume. Imagem só aparece no volume quando é inserida pelo botão de imagem do editor.</p>
        <div class="form-acoes">
            <button type="submit">Salvar dados editoriais</button>
        </div>
    </form>

    <h3>Capa</h3>
    <?php if ($temCapa): ?>
        <div class="acoes-icones">
            <span>Capa enviada: <strong><?php echo $esc($campoMontagem('capa_nome_original')); ?></strong></span>
            <a href="<?php echo url('anaisMontagem/baixarCapa/' . $eventoId); ?>" class="btn-icone" title="Abrir a capa enviada" target="_blank" rel="noopener"><?php echo $svgBaixar; ?></a>
            <form method="post" action="<?php echo url('anaisMontagem/removerCapa/' . $eventoId); ?>" onsubmit="return confirm('Remover a capa enviada? O volume volta a usar a capa simples gerada pelo sistema.');"><?= campoCsrf() ?>
                <button type="submit" class="btn-icone" title="Remover a capa enviada"><?php echo $svgRemover; ?></button>
            </form>
        </div>
    <?php else: ?>
        <p>Nenhuma capa enviada. Sem capa, o sistema gera uma capa simples com o título, o subtítulo, o nome do evento, o local e o ano e o ISSN ou ISBN.</p>
    <?php endif; ?>

    <form method="post" action="<?php echo url('anaisMontagem/enviarCapa/' . $eventoId); ?>" enctype="multipart/form-data"><?= campoCsrf() ?>
        <label><?php echo $temCapa ? 'Trocar a capa' : 'Enviar a capa pronta'; ?> (PDF de uma página, até <?php echo $esc($limiteCapaMB); ?> MB):
            <input type="file" name="capa" accept="application/pdf" required>
        </label>
        <p style="color:#555;font-size:0.9em;">Se o arquivo for recusado, salve-o de novo como PDF/A-1b e envie outra vez.</p>
        <div class="form-acoes">
            <button type="submit"><?php echo $temCapa ? 'Trocar capa' : 'Enviar capa'; ?></button>
        </div>
    </form>
</fieldset>

<fieldset id="bloco-comissoes">
    <legend>3. Comissões</legend>
    <p style="color:#555;font-size:0.9em;">Cada comissão aparece nas páginas iniciais do volume, na ordem desta lista, com os seus membros (nome, função e instituição separados por vírgula). Arraste as comissões e os membros para mudar a ordem; a nova ordem é salva sozinha.</p>

    <?php if (empty($comissoes)): ?>
        <p>Nenhuma comissão cadastrada ainda.</p>
    <?php else: ?>
        <ul class="reordenar-lista" data-reordenar-rota="anaisMontagem/reordenarComissoes/<?php echo $eventoId; ?>">
            <?php foreach ($comissoes as $indiceComissao => $comissao): ?>
            <li class="reordenar-item" draggable="true" data-id="<?php echo (int) $comissao['id']; ?>" style="align-items:flex-start;">
                <span class="reordenar-alca" aria-hidden="true" title="Arraste para reordenar">⠿</span>
                <?php /* Botoes da comissao ANTES da lista de membros no codigo
                (a ordem visual continua no fim, por "order"): o script de
                arrastar pega o primeiro botao de mover dentro do item, e com a
                lista de membros antes ele pegaria o botao de um membro. */ ?>
                <div class="reordenar-botoes" style="order:3;">
                    <button type="button" class="btn-icone" data-mover="cima" aria-label="Mover comissão para cima" <?php echo $indiceComissao === 0 ? 'disabled' : ''; ?>>▲</button>
                    <button type="button" class="btn-icone" data-mover="baixo" aria-label="Mover comissão para baixo" <?php echo $indiceComissao === count($comissoes) - 1 ? 'disabled' : ''; ?>>▼</button>
                </div>
                <div class="acoes-icones" style="order:2;">
                    <a href="<?php echo url('anaisMontagem/editarComissao/' . $eventoId . '/' . (int) $comissao['id']); ?>" class="btn-icone" title="Editar o nome da comissão"><?php echo $svgEditar; ?></a>
                    <form method="post" action="<?php echo url('anaisMontagem/removerComissao/' . $eventoId); ?>" onsubmit="return confirm('Remover esta comissão e todos os seus membros?');"><?= campoCsrf() ?>
                        <input type="hidden" name="id" value="<?php echo (int) $comissao['id']; ?>">
                        <button type="submit" class="btn-icone" title="Remover a comissão e os seus membros"><?php echo $svgRemover; ?></button>
                    </form>
                </div>
                <div class="reordenar-conteudo">
                    <strong><?php echo $esc($comissao['nome']); ?></strong>
                    <?php if (empty($comissao['membros'])): ?>
                        <p style="color:#555;font-size:0.9em;margin:0.4rem 0 0;">Nenhum membro cadastrado.</p>
                    <?php else: ?>
                        <ul class="reordenar-lista" data-reordenar-rota="anaisMontagem/reordenarMembros/<?php echo $eventoId; ?>/<?php echo (int) $comissao['id']; ?>" style="margin-top:0.5rem;">
                            <?php foreach ($comissao['membros'] as $indiceMembro => $membro): ?>
                            <?php
                            $partesMembro = [];
                            foreach (['nome', 'funcao', 'instituicao'] as $campoMembro) {
                                if (isset($membro[$campoMembro]) && trim((string) $membro[$campoMembro]) !== '') {
                                    $partesMembro[] = trim((string) $membro[$campoMembro]);
                                }
                            }
                            ?>
                            <li class="reordenar-item" draggable="true" data-id="<?php echo (int) $membro['id']; ?>">
                                <span class="reordenar-alca" aria-hidden="true" title="Arraste para reordenar">⠿</span>
                                <div class="reordenar-conteudo"><?php echo $esc(implode(', ', $partesMembro)); ?></div>
                                <div class="acoes-icones">
                                    <a href="<?php echo url('anaisMontagem/editarMembro/' . $eventoId . '/' . (int) $membro['id']); ?>" class="btn-icone" title="Editar membro"><?php echo $svgEditar; ?></a>
                                    <form method="post" action="<?php echo url('anaisMontagem/removerMembro/' . $eventoId); ?>" onsubmit="return confirm('Remover este membro da comissão?');"><?= campoCsrf() ?>
                                        <input type="hidden" name="id" value="<?php echo (int) $membro['id']; ?>">
                                        <button type="submit" class="btn-icone" title="Remover membro"><?php echo $svgRemover; ?></button>
                                    </form>
                                </div>
                                <div class="reordenar-botoes">
                                    <button type="button" class="btn-icone" data-mover="cima" aria-label="Mover membro para cima" <?php echo $indiceMembro === 0 ? 'disabled' : ''; ?>>▲</button>
                                    <button type="button" class="btn-icone" data-mover="baixo" aria-label="Mover membro para baixo" <?php echo $indiceMembro === count($comissao['membros']) - 1 ? 'disabled' : ''; ?>>▼</button>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <h3>Incluir comissão</h3>
    <form method="post" action="<?php echo url('anaisMontagem/novaComissao/' . $eventoId); ?>"><?= campoCsrf() ?>
        <label>Nome da comissão:
            <input type="text" name="nome" required maxlength="<?php echo (int) \App\Services\EventoAnaisMontagemService::NOME_COMISSAO_MAXIMO; ?>" placeholder="Ex.: Comissão Organizadora">
        </label>
        <div class="form-acoes">
            <button type="submit">Incluir comissão</button>
        </div>
    </form>

    <?php if (!empty($comissoes)): ?>
        <h3>Incluir membro</h3>
        <form method="post" action="<?php echo url('anaisMontagem/novoMembro/' . $eventoId); ?>"><?= campoCsrf() ?>
            <label>Comissão:
                <select name="comissao_id" required>
                    <?php foreach ($comissoes as $comissao): ?>
                        <option value="<?php echo (int) $comissao['id']; ?>"><?php echo $esc($comissao['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Nome:
                <input type="text" name="nome" required maxlength="<?php echo (int) \App\Services\EventoAnaisMontagemService::NOME_MEMBRO_MAXIMO; ?>">
            </label>
            <label>Função (opcional):
                <input type="text" name="funcao" maxlength="<?php echo (int) \App\Services\EventoAnaisMontagemService::FUNCAO_MAXIMA; ?>" placeholder="Ex.: Presidente">
            </label>
            <label>Instituição (opcional):
                <input type="text" name="instituicao" maxlength="<?php echo (int) \App\Services\EventoAnaisMontagemService::INSTITUICAO_MAXIMA; ?>">
            </label>
            <div class="form-acoes">
                <button type="submit">Incluir membro</button>
            </div>
        </form>
    <?php endif; ?>
</fieldset>

<fieldset id="bloco-trabalhos">
    <legend>4. Trabalhos</legend>
    <?php if (!$resultadoPublicado): ?>
        <p><span class="selo-situacao laranja">Resultado não publicado</span> Os trabalhos aparecem aqui depois que o resultado de Trabalhos é publicado (aba Resultado).</p>
    <?php elseif (empty($trabalhos)): ?>
        <p>Nenhum trabalho consta nos Anais. Confira a aba Trabalhos nos Anais.</p>
    <?php else: ?>
        <p>
            <strong><?php echo count($trabalhos) - $pendentes; ?></strong> de <?php echo count($trabalhos); ?> trabalho(s) com a versão final enviada.
            <?php if ($pendentes > 0): ?>
                <span class="selo-situacao laranja"><?php echo $pendentes; ?> pendente(s)</span>
            <?php else: ?>
                <span class="selo-situacao verde">Todos enviados</span>
            <?php endif; ?>
        </p>
        <p style="color:#555;font-size:0.9em;">A lista está na ordem do volume. Sem ordem definida, os trabalhos seguem a ordem dos eixos temáticos e, dentro de cada eixo, o título. Arraste para mudar; a nova ordem é salva sozinha. O sumário agrupa os trabalhos por eixo, então mantenha juntos os do mesmo eixo. Trabalho retirado dos Anais sai pela aba Trabalhos nos Anais; para dar mais tempo a quem falta, mude o prazo no bloco 1.</p>

        <ul class="reordenar-lista" data-reordenar-rota="anaisMontagem/reordenarTrabalhos/<?php echo $eventoId; ?>">
            <?php foreach ($trabalhos as $indiceTrabalho => $linhaTrabalho): ?>
            <li class="reordenar-item" draggable="true" data-id="<?php echo (int) $linhaTrabalho['id']; ?>">
                <span class="reordenar-alca" aria-hidden="true" title="Arraste para reordenar">⠿</span>
                <div class="reordenar-conteudo">
                    <strong><?php echo $esc($linhaTrabalho['titulo']); ?></strong>
                    <br>
                    <small>
                        Protocolo nº <?php echo (int) $linhaTrabalho['id']; ?>
                        <?php if ($linhaTrabalho['eixo_nome'] !== null && $linhaTrabalho['eixo_nome'] !== ''): ?>
                            &middot; Eixo: <?php echo $esc($linhaTrabalho['eixo_nome']); ?>
                        <?php endif; ?>
                        <?php if ($linhaTrabalho['autor_principal_nome'] !== ''): ?>
                            &middot; Autor principal: <?php echo $esc($linhaTrabalho['autor_principal_nome']); ?>
                        <?php endif; ?>
                    </small>
                    <br>
                    <?php if ($linhaTrabalho['arquivo'] !== null): ?>
                        <span class="status-pill verde">Enviado em <?php echo $esc(formatarDataHora($linhaTrabalho['arquivo']['enviado_em'])); ?> (<?php echo $esc($rotuloPaginas($linhaTrabalho['arquivo']['paginas'])); ?>)</span>
                    <?php else: ?>
                        <span class="status-pill laranja">Pendente</span>
                    <?php endif; ?>
                </div>
                <div class="acoes-icones">
                    <?php if ($linhaTrabalho['arquivo'] !== null): ?>
                        <a href="<?php echo url('anaisMontagem/baixarPdfTrabalho/' . $eventoId . '/' . (int) $linhaTrabalho['id']); ?>" class="btn-icone" title="Abrir a versão final enviada" target="_blank" rel="noopener"><?php echo $svgBaixar; ?></a>
                    <?php endif; ?>
                </div>
                <div class="reordenar-botoes">
                    <button type="button" class="btn-icone" data-mover="cima" aria-label="Mover trabalho para cima" <?php echo $indiceTrabalho === 0 ? 'disabled' : ''; ?>>▲</button>
                    <button type="button" class="btn-icone" data-mover="baixo" aria-label="Mover trabalho para baixo" <?php echo $indiceTrabalho === count($trabalhos) - 1 ? 'disabled' : ''; ?>>▼</button>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</fieldset>

<fieldset id="bloco-gerar">
    <legend>5. Gerar o volume</legend>
    <p style="color:#555;font-size:0.9em;">O sistema junta a capa, as páginas iniciais (folha de rosto, ficha catalográfica, expediente, comissões e apresentação), o sumário e a versão final de cada trabalho, na ordem do bloco 4. O número de cada página sai no rodapé a partir do primeiro trabalho; as páginas anteriores contam na sequência, sem número impresso, e o sumário aponta para esses números. A prévia é montada por uma rotina agendada do servidor e fica pronta em alguns minutos. Ela entra como nova versão na aba Anais, sem publicar: confira o arquivo e publique por lá.</p>

    <?php if ($impedimento !== null): ?>
        <p><span class="selo-situacao laranja">Geração indisponível</span> <?php echo $esc($impedimento); ?></p>
        <div class="form-acoes">
            <button type="button" disabled>Gerar prévia do volume</button>
        </div>
    <?php else: ?>
        <?php
        $textoGerar = 'Gerar a prévia do volume agora? O pedido entra na fila e a versão gerada aparece na aba Anais em alguns minutos, sem publicar.';
        $aoGerar = 'if (!confirm(' . json_encode($textoGerar) . ')) { return false; } this.querySelector(\'button\').disabled = true;';
        ?>
        <form method="post" action="<?php echo url('anaisMontagem/gerar/' . $eventoId); ?>" onsubmit="<?php echo $esc($aoGerar); ?>"><?= campoCsrf() ?>
            <div class="form-acoes">
                <button type="submit">Gerar prévia do volume</button>
            </div>
        </form>
    <?php endif; ?>

    <h3>Últimos pedidos</h3>
    <?php if (empty($geracoes)): ?>
        <p>Nenhum pedido de geração ainda.</p>
    <?php else: ?>
        <table border="1" cellpadding="6">
            <tr><th>Pedido</th><th>Situação</th><th>Pedido em</th><th>Início</th><th>Fim</th><th>Resultado</th></tr>
            <?php foreach ($geracoes as $geracao): ?>
            <?php
            $situacaoPedido = isset($situacoesPedido[$geracao['situacao']]) ? $situacoesPedido[$geracao['situacao']] : ['rotulo' => $geracao['situacao'], 'cor' => ''];
            $esperaLonga = $geracao['situacao'] === 'pendente' && (time() - strtotime($geracao['solicitado_em'])) > 600;
            ?>
            <tr>
                <td>nº <?php echo (int) $geracao['id']; ?></td>
                <td><span class="status-pill <?php echo $esc($situacaoPedido['cor']); ?>"><?php echo $esc($situacaoPedido['rotulo']); ?></span></td>
                <td>
                    <?php echo $esc(formatarDataHora($geracao['solicitado_em'])); ?>
                    <?php if (!empty($geracao['solicitado_por_nome'])): ?><br><small>por <?php echo $esc($geracao['solicitado_por_nome']); ?></small><?php endif; ?>
                </td>
                <td><?php echo $esc(formatarDataHora($geracao['iniciado_em'])); ?></td>
                <td><?php echo $esc(formatarDataHora($geracao['concluido_em'])); ?></td>
                <td>
                    <?php if ($geracao['situacao'] === 'concluida' && $geracao['versao_numero'] !== null): ?>
                        Versão <?php echo (int) $geracao['versao_numero']; ?> gerada: <a href="<?php echo url('trabalhoAnais/index/' . $eventoId); ?>">confira e publique na aba Anais</a>.
                    <?php elseif ($geracao['situacao'] === 'concluida'): ?>
                        A versão gerada por este pedido foi removida na aba Anais.
                    <?php endif; ?>
                    <?php if (!empty($geracao['mensagem'])): ?>
                        <br><small><?php echo $esc($geracao['mensagem']); ?></small>
                    <?php endif; ?>
                    <?php if ($esperaLonga): ?>
                        <br><small>O pedido está na fila há mais de dez minutos. Confira com o suporte técnico se a rotina agendada de geração dos Anais está ativa no servidor.</small>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</fieldset>
