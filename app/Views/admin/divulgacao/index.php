<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 56, reduzida na Fase 58: acompanhamento do modulo, so' com o estado,
 * os numeros e os totais por rede, por acao e por dia.
 *
 * A lista nominal das comprovacoes mudou para a aba "Comprovações", que
 * segue o padrao de tabela do projeto: barra de filtros, selecao por linha,
 * operacoes em lote e paginacao.
 */
?>
<div class="pagina-titulo-acoes">
    <h1>Divulgação: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('divulgacao/comprovacoes/' . (int) $evento['id']); ?>" class="btn-acao">Comprovações</a>
        <a href="<?php echo url('divulgacao/configuracoes/' . (int) $evento['id']); ?>" class="btn-acao">Configurações</a>
        <a href="<?php echo url('eventos/editar/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<p style="color:#555;font-size:0.9em;">
    O participante informa que publicou sobre o evento numa rede social, ou que passou a seguir um canal
    indicado pela organização, e anexa a prova. Os pontos são creditados no momento do envio, com o valor vigente
    naquele instante, sem conferência prévia. A auditoria é feita aqui, por amostragem: identificada uma falha, o
    Administrador anula a pontuação com justificativa, que aparece para a pessoa. Em "Qualquer rede", a rede e a
    conta não são conferidas: vale a imagem da tela. A soma de todos os pontos está em Gamificação, Classificação.
    Os números abaixo não contam comprovação excluída.
</p>

<?php if (!empty($gincanaEncerrada)): ?>
    <p class="status-pill laranja">A gincana deste evento foi encerrada e a classificação está congelada: nenhuma pontuação pode ser anulada ou restabelecida.</p>
<?php endif; ?>

<div class="admin-card">
    <p>
        <span class="status-pill <?php echo (int) $config['ativo'] === 1 ? 'verde' : 'laranja'; ?>">
            <?php echo (int) $config['ativo'] === 1 ? 'Divulgação ativada' : 'Divulgação desativada'; ?>
        </span>
    </p>
    <?php /* O período é o que o Administrador escreveu em Configurações. Com o
    módulo desligado não há período nenhum a mostrar, e com os dois campos em
    branco não há limite de data: a tela nunca anuncia um período que ninguém
    definiu. */ ?>
    <?php if ((int) $config['ativo'] === 1): ?>
        <p>
            <?php if ($janelaTexto !== ''): ?>
                Comprovações valem <?php echo htmlspecialchars($janelaTexto, ENT_QUOTES, 'UTF-8'); ?>.
            <?php else: ?>
                Sem limite de data: as comprovações valem enquanto a divulgação estiver ativada.
            <?php endif; ?>
        </p>
    <?php endif; ?>
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
                    <td><?php echo $linha['tipo_acao'] === 'acompanhar' ? 'Passou a seguir' : 'Publicação'; ?></td>
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

<p style="color:#555;font-size:0.9em;">
    A lista das comprovações, com filtros, seleção e operações em lote, fica na aba "Comprovações".
</p>
