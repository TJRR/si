<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 56: acompanhamento das comprovacoes de divulgacao.
 *
 * A lista e' NOMINAL, ao contrario da de Conexoes: a conferencia por
 * amostragem prevista no documento da dinamica de pontos depende de comparar
 * a prova com a conta que a pessoa cadastrou em "Meu Perfil".
 *
 * A imagem so' abre para o Administrador ($podeEditar): e' o unico dado de
 * terceiro nao consentido desta fase. O endereco da publicacao, que e'
 * conteudo publico, aparece para os dois perfis, revalidado por
 * linkHttpValido(), escapado, em aba nova e sem repassar a origem.
 */
?>
<div class="pagina-titulo-acoes">
    <h1>Divulgação: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('divulgacao/configuracoes/' . (int) $evento['id']); ?>" class="btn-acao">Configurações</a>
        <a href="<?php echo url('eventos/editar/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<p style="color:#555;font-size:0.9em;">
    O participante informa que publicou sobre o evento numa rede social, ou que passou a acompanhar um canal do
    Tribunal, e anexa a prova. Os pontos são creditados no momento do envio, com o valor vigente naquele
    instante. A conferência é feita aqui, por amostragem: identificada uma falha, o Administrador anula a
    pontuação com justificativa, que aparece para a pessoa. A classificação geral do evento não faz parte
    deste módulo.
</p>

<div class="admin-card">
    <p>
        <span class="status-pill <?php echo (int) $config['ativo'] === 1 ? 'verde' : 'laranja'; ?>">
            <?php echo (int) $config['ativo'] === 1 ? 'Divulgação ativada' : 'Divulgação desativada'; ?>
        </span>
    </p>
    <p>
        <?php if ($config['data_inicio'] !== null && $config['data_fim'] !== null): ?>
            Comprovações valem de <?php echo htmlspecialchars(formatarData($config['data_inicio']), ENT_QUOTES, 'UTF-8'); ?>
            a <?php echo htmlspecialchars(formatarData($config['data_fim']), ENT_QUOTES, 'UTF-8'); ?>.
        <?php else: ?>
            Comprovações valem nas datas do evento:
            <?php echo htmlspecialchars(formatarData($evento['data_inicio']), ENT_QUOTES, 'UTF-8'); ?> a
            <?php echo htmlspecialchars(formatarData($evento['data_fim']), ENT_QUOTES, 'UTF-8'); ?>.
        <?php endif; ?>
    </p>
</div>

<div class="admin-card">
    <p><strong><?php echo (int) $numeros['validas']; ?></strong> <?php echo (int) $numeros['validas'] === 1 ? 'comprovação válida' : 'comprovações válidas'; ?></p>
    <p><strong><?php echo (int) $numeros['pontos']; ?></strong> <?php echo (int) $numeros['pontos'] === 1 ? 'ponto creditado no total' : 'pontos creditados no total'; ?></p>
    <p><strong><?php echo (int) $numeros['pessoas']; ?></strong> <?php echo (int) $numeros['pessoas'] === 1 ? 'participante enviou comprovação' : 'participantes enviaram comprovação'; ?></p>
    <?php if ((int) $numeros['anuladas'] > 0): ?>
        <p><strong><?php echo (int) $numeros['anuladas']; ?></strong> <?php echo (int) $numeros['anuladas'] === 1 ? 'comprovação anulada' : 'comprovações anuladas'; ?></p>
    <?php endif; ?>
</div>

<?php if (!empty($porRedeETipo)): ?>
    <table>
        <thead>
            <tr>
                <th>Rede social</th>
                <th>Ação</th>
                <th>Comprovações</th>
                <th>Pontos válidos</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($porRedeETipo as $linha): ?>
                <tr>
                    <td><?php echo htmlspecialchars(isset($rotulosRede[$linha['rede']]) ? $rotulosRede[$linha['rede']] : $linha['rede'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo $linha['tipo_acao'] === 'acompanhar' ? 'Passou a acompanhar' : 'Publicação'; ?></td>
                    <td><?php echo (int) $linha['total']; ?></td>
                    <td><?php echo (int) $linha['pontos']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php if (!empty($porDia)): ?>
    <table>
        <thead>
            <tr>
                <th>Dia</th>
                <th>Comprovações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($porDia as $linha): ?>
                <tr>
                    <td><?php echo htmlspecialchars(formatarData($linha['dia']), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo (int) $linha['total']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h2>Comprovações enviadas</h2>

<form method="get" action="<?php echo url('divulgacao/index/' . (int) $evento['id']); ?>" style="margin-bottom:12px;">
    <label>Rede social
        <select name="rede" onchange="this.form.submit()">
            <option value="">Todas</option>
            <?php foreach ($rotulosRede as $chave => $rotulo): ?>
                <option value="<?php echo htmlspecialchars($chave, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $filtros['rede'] === $chave ? ' selected' : ''; ?>><?php echo htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Ação
        <select name="tipo_acao" onchange="this.form.submit()">
            <option value="">Todas</option>
            <option value="publicacao"<?php echo $filtros['tipo_acao'] === 'publicacao' ? ' selected' : ''; ?>>Publicação</option>
            <option value="acompanhar"<?php echo $filtros['tipo_acao'] === 'acompanhar' ? ' selected' : ''; ?>>Passou a acompanhar</option>
        </select>
    </label>
    <label>Situação
        <select name="situacao" onchange="this.form.submit()">
            <option value="">Todas</option>
            <option value="validas"<?php echo $filtros['situacao'] === 'validas' ? ' selected' : ''; ?>>Válidas</option>
            <option value="anuladas"<?php echo $filtros['situacao'] === 'anuladas' ? ' selected' : ''; ?>>Anuladas</option>
        </select>
    </label>
</form>

<?php if (empty($comprovacoes)): ?>
    <p>Nenhuma comprovação registrada neste evento até o momento.</p>
<?php endif; ?>

<?php foreach ($comprovacoes as $comprovacao): ?>
    <?php
    $anulada = $comprovacao['anulado_em'] !== null;
    $repetida = !empty($comprovacao['arquivo_sha256']) && isset($resumosRepetidos[$comprovacao['arquivo_sha256']]);
    $redesDaPessoa = [];

    if (!empty($comprovacao['participante_redes'])) {
        $decodificado = json_decode($comprovacao['participante_redes'], true);
        $redesDaPessoa = is_array($decodificado) ? $decodificado : [];
    }
    ?>
    <div class="admin-card">
        <p>
            <strong><?php echo htmlspecialchars($comprovacao['participante_nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
            ·
            <?php echo htmlspecialchars(isset($rotulosRede[$comprovacao['rede']]) ? $rotulosRede[$comprovacao['rede']] : $comprovacao['rede'], ENT_QUOTES, 'UTF-8'); ?>
            ·
            <?php echo $comprovacao['tipo_acao'] === 'acompanhar' ? 'Passou a acompanhar' : 'Publicação'; ?>
            ·
            <?php echo date('d/m/Y H:i', strtotime($comprovacao['enviado_em'])); ?>
        </p>
        <p>
            <?php if ($anulada): ?>
                <span class="status-pill vermelho">Anulada</span>
            <?php elseif ((int) $comprovacao['pontos_creditados'] > 0): ?>
                <span class="status-pill verde"><?php echo (int) $comprovacao['pontos_creditados']; ?> ponto(s)</span>
            <?php else: ?>
                <span class="status-pill laranja">Sem pontos</span>
            <?php endif; ?>
            <?php if ($repetida): ?>
                <span class="status-pill roxo">Mesma imagem em <?php echo (int) $resumosRepetidos[$comprovacao['arquivo_sha256']]; ?> participantes</span>
            <?php endif; ?>
        </p>
        <?php if (isset($redesDaPessoa[$comprovacao['rede']]) && linkHttpValido($redesDaPessoa[$comprovacao['rede']])): ?>
            <p>
                Perfil cadastrado:
                <a href="<?php echo htmlspecialchars($redesDaPessoa[$comprovacao['rede']], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($redesDaPessoa[$comprovacao['rede']], ENT_QUOTES, 'UTF-8'); ?></a>
            </p>
        <?php endif; ?>
        <?php if (!empty($comprovacao['endereco']) && linkHttpValido($comprovacao['endereco'])): ?>
            <p>
                Publicação:
                <a href="<?php echo htmlspecialchars($comprovacao['endereco'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($comprovacao['endereco'], ENT_QUOTES, 'UTF-8'); ?></a>
            </p>
        <?php endif; ?>
        <?php if (!empty($comprovacao['arquivo_path'])): ?>
            <?php if ($podeEditar): ?>
                <p>
                    <a href="<?php echo url('divulgacao/imagem/' . (int) $evento['id'] . '/' . (int) $comprovacao['id']); ?>" target="_blank" rel="noopener noreferrer">Ver a imagem enviada</a>
                </p>
            <?php else: ?>
                <p><small>Imagem enviada. Só o Administrador abre a imagem.</small></p>
            <?php endif; ?>
        <?php elseif ($comprovacao['arquivo_removido_em'] !== null): ?>
            <p><small>Imagem apagada em <?php echo date('d/m/Y', strtotime($comprovacao['arquivo_removido_em'])); ?>, conforme a política de retenção.</small></p>
        <?php endif; ?>
        <?php if ($anulada): ?>
            <p>
                Anulada em <?php echo date('d/m/Y H:i', strtotime($comprovacao['anulado_em'])); ?>
                <?php if (!empty($comprovacao['anulado_por_nome'])): ?>
                    por <?php echo htmlspecialchars($comprovacao['anulado_por_nome'], ENT_QUOTES, 'UTF-8'); ?>
                <?php endif; ?>
                <?php if (!empty($comprovacao['motivo_anulacao'])): ?>
                    <br>Motivo: <?php echo htmlspecialchars($comprovacao['motivo_anulacao'], ENT_QUOTES, 'UTF-8'); ?>
                <?php endif; ?>
            </p>
            <?php if ($podeEditar): ?>
                <form method="post" action="<?php echo url('divulgacao/reverterAnulacao/' . (int) $evento['id'] . '/' . (int) $comprovacao['id']); ?>" onsubmit="return confirm('Desfazer a anulação e devolver os pontos?');"><?= campoCsrf() ?>
                    <button type="submit">Desfazer anulação</button>
                </form>
            <?php endif; ?>
        <?php elseif ($podeEditar): ?>
            <form method="post" action="<?php echo url('divulgacao/anular/' . (int) $evento['id'] . '/' . (int) $comprovacao['id']); ?>" onsubmit="return confirm('Anular a pontuação desta comprovação?');"><?= campoCsrf() ?>
                <label>Motivo da anulação
                    <input type="text" name="motivo" maxlength="500" required placeholder="O participante lê este motivo">
                </label>
                <button type="submit">Anular pontuação</button>
            </form>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
