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
    <p style="color:#555;font-size:0.9em;">A emissão de certificado em si é uma etapa futura: aqui só se guarda a decisão.</p>

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
    <p style="color:#555;font-size:0.9em;">Usado pela sub-aba "Presenças" e futuramente pelo certificado; não afeta se a confirmação de presença é aceita, só se ela conta como presença efetiva.</p>

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
        <p style="color:#555;font-size:0.9em;">Informe este código verbalmente durante a atividade: quem está participando online digita esse código no aplicativo (em vez de ler o QR) para confirmar presença.</p>
    <?php endif; ?>

    <div class="form-acoes">
        <a href="<?php echo url('atividades/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>
