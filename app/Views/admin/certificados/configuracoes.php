<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 59: a regua de elegibilidade, os textos, os planos de fundo e a
 * liberacao da emissao.
 *
 * Todo campo de numero nasce EM BRANCO e em branco significa "nao exige": o
 * sistema nao assume numero que o Administrador nao escreveu (licao da Fase
 * 58). O proprio quadro abaixo da regua diz, em portugues, o que esta' em
 * vigor naquele momento.
 */
$eventoId = (int) $evento['id'];
$tipoEvento = \App\Services\CertificadoElegibilidadeService::TIPO_EVENTO;
$tipoAtividade = \App\Services\CertificadoElegibilidadeService::TIPO_ATIVIDADE;
$tipoApresentacao = \App\Services\CertificadoElegibilidadeService::TIPO_APRESENTACAO;
$exigencias = \App\Services\CertificadoElegibilidadeService::exigenciasEmVigor($config);
?>
<div class="pagina-titulo-acoes">
    <h1>Configurações dos certificados: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
</div>

<?php if (!$podeEditar): ?>
    <p class="status-pill laranja">Somente leitura: só o Administrador altera estas configurações.</p>
<?php endif; ?>

<div class="admin-card">
    <h2>Emissão</h2>
    <p>
        A emissão tem duas condições. A primeira vale para todos, inclusive para a organização: ela começa
        depois do último dia do evento (<?php echo formatarData($evento['data_fim']); ?>), porque até lá a
        carga horária ainda pode mudar e o documento é guardado exatamente como foi emitido. A segunda vale só
        para quem participou: a chave abaixo.
    </p>
    <p>
        Situação de agora:
        <?php if ($eventoTerminou): ?>
            <span class="status-pill verde">o evento já terminou</span>
        <?php else: ?>
            <span class="status-pill laranja">o evento ainda não terminou</span>
        <?php endif; ?>
        <?php if ($config['emissao_liberada_em'] !== null): ?>
            <span class="status-pill verde">chave aberta em <?php echo formatarDataHora($config['emissao_liberada_em']); ?></span>
        <?php else: ?>
            <span class="status-pill laranja">chave fechada</span>
        <?php endif; ?>
        <?php if ($emissaoAberta): ?>
            <span class="status-pill verde">quem participou já retira no aplicativo</span>
        <?php endif; ?>
    </p>
    <?php if ($podeEditar): ?>
        <form method="post" action="<?php echo url('certificados/liberar/' . $eventoId); ?>"><?= campoCsrf() ?>
            <?php if ($config['emissao_liberada_em'] === null): ?>
                <input type="hidden" name="abrir" value="1">
                <?php if ($eventoTerminou && (int) $config['ativo'] === 1): ?>
                    <label style="display:block;">
                        <input type="checkbox" name="avisar" value="1" checked>
                        Avisar quem ainda não foi avisado (sino do aplicativo e e-mail)
                    </label>
                <?php else: ?>
                    <p><small>O aviso aos participantes fica disponível depois do último dia do evento, com o módulo ligado: até lá o certificado ainda não pode ser retirado.</small></p>
                <?php endif; ?>
                <button type="submit">Abrir a emissão para quem participou</button>
            <?php else: ?>
                <button type="submit" onclick="return confirm('Fechar a emissão para quem participou? Os certificados já emitidos continuam valendo.');">Fechar a emissão</button>
            <?php endif; ?>
        </form>

        <?php if ($config['emissao_liberada_em'] !== null): ?>
            <form method="post" action="<?php echo url('certificados/avisar/' . $eventoId); ?>"><?= campoCsrf() ?>
                <?php if ($emissaoAberta): ?>
                    <p><?php echo (int) $pendentesAviso; ?> certificado(s) disponível(is) ainda sem aviso.</p>
                    <button type="submit" class="btn-acao" <?php echo (int) $pendentesAviso === 0 ? 'disabled' : ''; ?>>Avisar quem ainda não foi avisado</button>
                <?php else: ?>
                    <button type="submit" class="btn-acao" disabled>Avisar quem ainda não foi avisado</button>
                    <p><small>O aviso fica disponível depois do último dia do evento, com o módulo ligado: até lá o certificado ainda não pode ser retirado.</small></p>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>

<form method="post" action="<?php echo url('certificados/configuracoes/' . $eventoId); ?>"><?= campoCsrf() ?>
    <div class="admin-card">
        <h2>Módulo</h2>
        <label>
            <input type="checkbox" name="ativo" value="1" <?php echo (int) $config['ativo'] === 1 ? 'checked' : ''; ?>>
            Emitir certificados neste evento
        </label>
        <p><small>
            Desligado, nenhuma tela de participante mostra caminho para certificado e nenhuma emissão
            acontece. Os certificados já emitidos continuam guardados.
        </small></p>
    </div>

    <div class="admin-card">
        <h2>Quem tem direito ao certificado do evento</h2>
        <p><small>
            Deixe em branco o que não quiser exigir. Quem tem direito cumpre <em>todas</em> as exigências
            escritas. Quem conduziu atividade e quem avaliou trabalho entram pela própria designação, sem
            passar por estas exigências.
        </small></p>

        <label>Mínimo de atividades diferentes com presença
            <input type="number" name="min_atividades" min="1" max="999" value="<?php echo $config['min_atividades'] !== null ? (int) $config['min_atividades'] : ''; ?>">
        </label>
        <?php if (isset($erros['min_atividades'])): ?>
            <p class="status-pill vermelho"><?php echo htmlspecialchars($erros['min_atividades'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <label>Mínimo de dias diferentes com presença
            <input type="number" name="min_dias" min="1" max="999" value="<?php echo $config['min_dias'] !== null ? (int) $config['min_dias'] : ''; ?>">
        </label>
        <?php if (isset($erros['min_dias'])): ?>
            <p class="status-pill vermelho"><?php echo htmlspecialchars($erros['min_dias'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <label>Mínimo de horas apuradas
            <input type="number" name="min_horas" min="1" max="999" value="<?php echo $config['min_horas'] !== null ? (int) $config['min_horas'] : ''; ?>">
        </label>
        <p><small>
            A apuração soma a união dos horários das atividades com presença: duas atividades no mesmo horário
            contam uma vez, então o número nunca passa do tempo real do evento.
        </small></p>
        <?php if (isset($erros['min_horas'])): ?>
            <p class="status-pill vermelho"><?php echo htmlspecialchars($erros['min_horas'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <label>
            <input type="checkbox" name="exige_credenciamento" value="1" <?php echo (int) $config['exige_credenciamento'] === 1 ? 'checked' : ''; ?>>
            Exigir o credenciamento no local
        </label>
        <p><small>
            Quem não leu o código do credenciamento fica sem o certificado do evento. Confira antes a janela de
            leitura em Gamificação, Credenciamento: se ela cobrir só o primeiro dia, quem chegou depois não
            alcança.
        </small></p>

        <p>
            Em vigor agora:
            <?php if ($exigencias === []): ?>
                <strong>nenhuma exigência</strong>, então todo inscrito tem direito.
            <?php else: ?>
                <strong><?php echo count($exigencias); ?></strong> exigência(s).
            <?php endif; ?>
        </p>
    </div>

    <div class="admin-card">
        <h2>Carga horária da função de avaliador</h2>
        <label>Horas declaradas para quem avaliou trabalhos
            <input type="number" name="carga_horaria_avaliador_horas" min="1" max="999" value="<?php echo $config['carga_horaria_avaliador_horas'] !== null ? (int) $config['carga_horaria_avaliador_horas'] : ''; ?>">
            horas
        </label>
        <p><small>
            A avaliação acontece fora dos dias do evento e não tem horário registrado no sistema, então este
            número é declarado pela organização e vale igual para todos. Em branco, o certificado de quem
            avaliou sai sem carga horária. Só recebe quem foi designado e de fato lançou nota.
        </small></p>
        <?php if (isset($erros['carga_horaria_avaliador_horas'])): ?>
            <p class="status-pill vermelho"><?php echo htmlspecialchars($erros['carga_horaria_avaliador_horas'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
    </div>

    <div class="admin-card">
        <h2>Texto do certificado do evento</h2>
        <p><small>
            O editor já abre com um texto modelo, pronto para alterar: ele é ponto de partida, e nada é gravado
            antes de você salvar. As palavras-chave do seletor da barra são trocadas pelos dados de cada pessoa
            na emissão. Sem texto escrito, este certificado não é emitido.
        </small></p>
        <?php
        $nome = 'texto_evento_html';
        $valor = !empty($config['texto_evento_html'])
            ? $config['texto_evento_html']
            : \App\Services\CertificadoTextoService::modeloDoTipo($tipoEvento);
        $rotulo = null;
        $mostrarPalavrasChave = true;
        $palavrasChaveLista = $palavrasChave[$tipoEvento];
        include __DIR__ . '/../_editor_rico.php';
        ?>
        <?php if (isset($erros['texto_evento_html'])): ?>
            <p class="status-pill vermelho"><?php echo htmlspecialchars($erros['texto_evento_html'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <?php
        $campoUrl = 'fundo_url';
        $campoCor = 'fundo_cor';
        $valorUrl = $config['fundo_url'];
        $valorCor = $config['fundo_cor'];
        $rotuloFundo = 'Plano de fundo deste certificado';
        $eventoIdFundo = $eventoId;
        include __DIR__ . '/_seletor_fundo.php';
        ?>
    </div>

    <div class="admin-card">
        <h2>Texto do certificado de atividade</h2>
        <p><small>
            Um texto só serve a todas as atividades: use as palavras-chave da atividade para o nome, o período
            e o local. Só as atividades com "Emite certificado por esta atividade" marcada geram documento.
        </small></p>
        <?php
        $nome = 'texto_atividade_html';
        $valor = !empty($config['texto_atividade_html'])
            ? $config['texto_atividade_html']
            : \App\Services\CertificadoTextoService::modeloDoTipo($tipoAtividade);
        $rotulo = null;
        $mostrarPalavrasChave = true;
        $palavrasChaveLista = $palavrasChave[$tipoAtividade];
        include __DIR__ . '/../_editor_rico.php';
        ?>
        <?php if (isset($erros['texto_atividade_html'])): ?>
            <p class="status-pill vermelho"><?php echo htmlspecialchars($erros['texto_atividade_html'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <?php
        $campoUrl = 'fundo_atividade_url';
        $campoCor = 'fundo_atividade_cor';
        $valorUrl = $config['fundo_atividade_url'];
        $valorCor = $config['fundo_atividade_cor'];
        $rotuloFundo = 'Plano de fundo dos certificados de atividade';
        $eventoIdFundo = $eventoId;
        include __DIR__ . '/_seletor_fundo.php';
        ?>
        <p><small>
            Cada atividade pode escolher uma arte própria, no cadastro dela. Sem escolha na atividade, vale
            esta.
        </small></p>
    </div>

    <div class="admin-card">
        <h2>Texto do certificado de apresentação de trabalho</h2>
        <p><small>
            Vale para os autores de trabalho marcado como apresentado, na aba "Apresentações". Este documento
            não declara carga horária: o que ele atesta é a apresentação.
        </small></p>
        <?php
        $nome = 'texto_apresentacao_html';
        $valor = !empty($config['texto_apresentacao_html'])
            ? $config['texto_apresentacao_html']
            : \App\Services\CertificadoTextoService::modeloDoTipo($tipoApresentacao);
        $rotulo = null;
        $mostrarPalavrasChave = true;
        $palavrasChaveLista = $palavrasChave[$tipoApresentacao];
        include __DIR__ . '/../_editor_rico.php';
        ?>
        <?php if (isset($erros['texto_apresentacao_html'])): ?>
            <p class="status-pill vermelho"><?php echo htmlspecialchars($erros['texto_apresentacao_html'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <?php
        $campoUrl = 'fundo_apresentacao_url';
        $campoCor = 'fundo_apresentacao_cor';
        $valorUrl = $config['fundo_apresentacao_url'];
        $valorCor = $config['fundo_apresentacao_cor'];
        $rotuloFundo = 'Plano de fundo dos certificados de apresentação';
        $eventoIdFundo = $eventoId;
        include __DIR__ . '/_seletor_fundo.php';
        ?>
    </div>

    <?php if ($podeEditar): ?>
        <p><button type="submit">Salvar configurações</button></p>
    <?php endif; ?>
</form>
