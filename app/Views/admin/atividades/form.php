<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php $ehEdicao = $atividade !== null && isset($atividade['id']); ?>
<h1><?php echo $ehEdicao ? 'Editar atividade' : 'Nova atividade'; ?>: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if (!empty($erro)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo $ehEdicao ? url('atividades/editar/' . (int) $atividade['id']) : url('atividades/novo/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
    <label>Nome:
        <input type="text" name="nome" required value="<?php echo htmlspecialchars($atividade !== null ? (string) $atividade['nome'] : '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="150" size="60">
    </label><br>

    <fieldset>
        <legend>Descrição</legend>
        <?php
        $nome = 'descricao_html';
        $valor = $atividade !== null ? (string) $atividade['descricao_html'] : '';
        $rotulo = null;
        include __DIR__ . '/../_editor_rico.php';
        ?>
    </fieldset>

    <?php
    // Fase 51: tipo e destaque alimentam as seções "Destaques" e
    // "Programação" da página pública do evento. Sem tipo cadastrado, a
    // lista aparece vazia e a atividade fica sem etiqueta, sem impedir nada.
    $tipoAtual = $atividade !== null && isset($atividade['tipo_id']) ? (int) $atividade['tipo_id'] : 0;
    ?>
    <label>Tipo (etiqueta na página pública):
        <select name="tipo_id">
            <option value="">Sem tipo</option>
            <?php foreach ($tiposAtividade as $tipoAtividade): ?>
                <option value="<?php echo (int) $tipoAtividade['id']; ?>" <?php echo $tipoAtual === (int) $tipoAtividade['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($tipoAtividade['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <p style="color:#555;font-size:0.9em;">Os tipos são cadastrados em <a href="<?php echo url('atividadeTipos/index/' . (int) $evento['id']); ?>">Tipos de atividade</a>.</p>

    <label><input type="checkbox" name="destacar_na_pagina" value="1" <?php echo ($atividade !== null && !empty($atividade['destacar_na_pagina'])) ? 'checked' : ''; ?>> Destacar esta atividade na página pública do evento</label><br>

    <label>Local:
        <input type="text" name="local" value="<?php echo htmlspecialchars($atividade !== null ? (string) $atividade['local'] : '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="150" size="40">
    </label><br>

    <?php $modalidadeAtual = $atividade !== null && isset($atividade['modalidade']) ? (string) $atividade['modalidade'] : 'presencial'; ?>
    <label>Modalidade:
        <select name="modalidade">
            <option value="presencial" <?php echo $modalidadeAtual === 'presencial' ? 'selected' : ''; ?>>Presencial</option>
            <option value="online" <?php echo $modalidadeAtual === 'online' ? 'selected' : ''; ?>>Online</option>
            <option value="hibrido" <?php echo $modalidadeAtual === 'hibrido' ? 'selected' : ''; ?>>Híbrido</option>
        </select>
    </label>
    <p style="color:#555;font-size:0.9em;">Presencial usa só o código impresso na sala; online usa só o código de presença online; híbrido aceita os dois, cada participante confirma pelo caminho que corresponde à sua forma real de participação.</p>

    <label>Início:
        <input type="datetime-local" id="campo-atividade-data-inicio" name="data_inicio" required value="<?php echo htmlspecialchars($atividade !== null && $atividade['data_inicio'] !== null ? str_replace(' ', 'T', substr((string) $atividade['data_inicio'], 0, 16)) : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>Fim:
        <input type="datetime-local" id="campo-atividade-data-fim" name="data_fim" required value="<?php echo htmlspecialchars($atividade !== null && $atividade['data_fim'] !== null ? str_replace(' ', 'T', substr((string) $atividade['data_fim'], 0, 16)) : '', ENT_QUOTES, 'UTF-8'); ?>">
    </label><br>

    <label>
        <input type="checkbox" name="exige_inscricao" value="1" <?php echo ($atividade !== null && !empty($atividade['exige_inscricao'])) ? 'checked' : ''; ?>>
        Exige inscrição prévia (quem está inscrito no evento decide se participa desta atividade)
    </label><br>

    <label>
        <input type="checkbox" name="emite_certificado" value="1" <?php echo ($atividade !== null && !empty($atividade['emite_certificado'])) ? 'checked' : ''; ?>>
        Emite certificado por esta atividade
    </label>
    <p style="color:#555;font-size:0.9em;">Marcada, quem confirmou presença nesta atividade e quem a conduziu passam a ter um certificado próprio dela, com a duração dela como carga horária. O texto do documento é um só para todas as atividades, em Certificados, Configurações.</p>

    <?php /* Fase 59: plano de fundo próprio desta atividade, pelo mesmo
    seletor da tela de configurações dos certificados. Sem escolha aqui, vale
    a arte das atividades em Certificados, Configurações. */ ?>
    <?php
    $campoUrl = 'certificado_fundo_url';
    $campoCor = 'certificado_fundo_cor';
    $valorUrl = $atividade !== null && isset($atividade['certificado_fundo_url']) ? $atividade['certificado_fundo_url'] : '';
    $valorCor = $atividade !== null && isset($atividade['certificado_fundo_cor']) ? $atividade['certificado_fundo_cor'] : '';
    $rotuloFundo = 'Plano de fundo do certificado desta atividade';
    $eventoIdFundo = (int) $evento['id'];
    $semPadraoFundo = true;
    include __DIR__ . '/../certificados/_seletor_fundo.php';
    ?>
    <p style="color:#555;font-size:0.9em;">Sem escolha aqui, vale a arte das atividades em Certificados, Configurações. Escolher uma arte ou uma cor nesta tela faz a atividade decidir por conta própria.</p>

    <label>Vagas (em branco = ilimitada):
        <input type="number" name="vagas" min="1" value="<?php echo htmlspecialchars($atividade !== null && $atividade['vagas'] !== null ? (string) $atividade['vagas'] : '', ENT_QUOTES, 'UTF-8'); ?>" style="width:6em;">
    </label><br>

    <label>
        <input type="checkbox" name="permite_lista_espera" value="1" <?php echo ($atividade !== null && !empty($atividade['permite_lista_espera'])) ? 'checked' : ''; ?>>
        Ao lotar, aceita lista de espera (sem marcar, novas inscrições são bloqueadas quando lotar)
    </label>
    <p style="color:#555;font-size:0.9em;">Só tem efeito quando "Vagas" tiver um número preenchido.</p>

    <?php $antecedenciaAtual = $atividade !== null && isset($atividade['antecedencia_abertura_presenca']) ? (string) $atividade['antecedencia_abertura_presenca'] : '60'; ?>
    <label>A confirmação de presença fica disponível a partir de:
        <select name="antecedencia_abertura_presenca">
            <?php foreach (['15', '30', '60'] as $minutos): ?>
                <option value="<?php echo $minutos; ?>" <?php echo $antecedenciaAtual === $minutos ? 'selected' : ''; ?>><?php echo $minutos; ?> minutos antes do início da atividade</option>
            <?php endforeach; ?>
        </select>
    </label>
    <p style="color:#555;font-size:0.9em;">Antes desse horário, a leitura do código é recusada, para evitar confirmar presença muito antes da atividade de fato acontecer.</p>

    <?php $toleranciaAtual = $atividade !== null && isset($atividade['tolerancia_presenca_efetiva']) ? (string) $atividade['tolerancia_presenca_efetiva'] : '100'; ?>
    <label>Considerar presença efetiva se a confirmação de presença ocorrer até:
        <select id="campo-atividade-tolerancia" name="tolerancia_presenca_efetiva">
            <?php foreach (['0', '25', '50', '75', '100'] as $percentual): ?>
                <option value="<?php echo $percentual; ?>" <?php echo $toleranciaAtual === $percentual ? 'selected' : ''; ?>><?php echo $percentual; ?>%</option>
            <?php endforeach; ?>
        </select>
    </label>
    <p style="color:#555;font-size:0.9em;">Usado pela sub-aba "Presenças"; não afeta se a confirmação de presença é aceita, só se ela conta como presença efetiva. Conta como efetiva a confirmação feita desde a abertura da leitura (os minutos antes do início escolhidos acima) até a porcentagem da duração escolhida aqui. A leitura é recusada depois do fim da atividade.</p>

    <?php
    $pontosPresencaAtual = $atividade !== null && isset($atividade['pontos_presenca']) && $atividade['pontos_presenca'] !== null ? (string) $atividade['pontos_presenca'] : '';
    $pontosPontualidadeAtual = $atividade !== null && isset($atividade['pontos_pontualidade']) && $atividade['pontos_pontualidade'] !== null ? (string) $atividade['pontos_pontualidade'] : '';
    ?>
    <label>Pontos de presença desta atividade:
        <input type="number" name="pontos_presenca" min="0" max="1000" step="1" style="width:6em;" value="<?php echo htmlspecialchars($pontosPresencaAtual, ENT_QUOTES, 'UTF-8'); ?>">
    </label>
    <label>Extra de pontualidade desta atividade:
        <input type="number" name="pontos_pontualidade" min="0" max="1000" step="1" style="width:6em;" value="<?php echo htmlspecialchars($pontosPontualidadeAtual, ENT_QUOTES, 'UTF-8'); ?>">
    </label>
    <p style="color:#555;font-size:0.9em;">Em branco, a atividade usa os valores do tipo (Tipos de atividade). Preencha só quando esta atividade tiver valor diferente do tipo; zero significa que ela não pontua. Mudar os valores não altera presença já pontuada.</p>

    <?php if ($ehEdicao && !empty($atividade['codigo_atividade'])): ?>
        <p>
            <strong>Código desta atividade:</strong> <?php echo htmlspecialchars($atividade['codigo_atividade'], ENT_QUOTES, 'UTF-8'); ?>,
            <a href="<?php echo url('atividades/codigo/' . (int) $atividade['id']); ?>" target="_blank" rel="noopener">Imprimir código</a>
        </p>
        <p style="color:#555;font-size:0.9em;">Afixe o cartaz impresso no espaço físico da atividade: o participante confirma presença apontando a câmera do aplicativo para ele.</p>
    <?php endif; ?>

    <?php if ($ehEdicao && !empty($atividade['codigo_presenca_online'])): ?>
        <p>
            <strong>Código de presença online desta atividade:</strong> <?php echo htmlspecialchars($atividade['codigo_presenca_online'], ENT_QUOTES, 'UTF-8'); ?>
        </p>
        <p style="color:#555;font-size:0.9em;">Informe este código assim que a sala virtual abrir, antes do início: quem participa pela internet digita esse código no aplicativo (em vez de ler o QR) para confirmar presença, e pontua como quem está na sala, inclusive com o extra de pontualidade.</p>
    <?php endif; ?>

    <div class="form-acoes">
        <a href="<?php echo url('atividades/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>
