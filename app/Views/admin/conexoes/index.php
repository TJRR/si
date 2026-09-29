<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Conexões: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('conexoes/configuracoes/' . (int) $evento['id']); ?>" class="btn-acao">Configurações</a>
        <a href="<?php echo url('eventos/editar/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<p style="color:#555;font-size:0.9em;">
    Cada participante lê o crachá de outro no aplicativo e as duas pessoas ficam conectadas, pontuando as duas
    com uma leitura só. Esta tela mostra apenas o volume: quem se conectou com quem é dado pessoal de cada
    participante e aparece somente na tela dele. A classificação geral do evento não faz parte deste módulo.
</p>

<div class="admin-card">
    <p>
        <span class="status-pill <?php echo (int) $config['ativo'] === 1 ? 'verde' : 'laranja'; ?>">
            <?php echo (int) $config['ativo'] === 1 ? 'Conexões ativadas' : 'Conexões desativadas'; ?>
        </span>
    </p>
    <p>
        <?php echo (int) $config['pontos_por_conexao']; ?>
        <?php echo (int) $config['pontos_por_conexao'] === 1 ? 'ponto por conexão' : 'pontos por conexão'; ?>
        ·
        <?php if ((int) $config['teto_conexoes_pontuadas'] > 0): ?>
            até <?php echo (int) $config['teto_conexoes_pontuadas']; ?> conexões pontuam por participante
        <?php else: ?>
            sem limite de conexões que pontuam
        <?php endif; ?>
    </p>
    <p>
        <small>
            Valem entre <?php echo htmlspecialchars(formatarData($evento['data_inicio']), ENT_QUOTES, 'UTF-8'); ?> e
            <?php echo htmlspecialchars(formatarData($evento['data_fim']), ENT_QUOTES, 'UTF-8'); ?>, as datas deste evento.
        </small>
    </p>
</div>

<div class="admin-card">
    <p><strong><?php echo (int) $totalConexoes; ?></strong> <?php echo (int) $totalConexoes === 1 ? 'conexão registrada' : 'conexões registradas'; ?></p>
    <p><strong><?php echo (int) $totalParticipantes; ?></strong> <?php echo (int) $totalParticipantes === 1 ? 'participante já se conectou' : 'participantes já se conectaram'; ?></p>
    <p><strong><?php echo (int) $totalPontos; ?></strong> <?php echo (int) $totalPontos === 1 ? 'ponto creditado no total' : 'pontos creditados no total'; ?></p>
</div>

<?php if (!empty($porDia)): ?>
    <table>
        <thead>
            <tr>
                <th>Dia</th>
                <th>Conexões</th>
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
<?php else: ?>
    <p>Nenhuma conexão registrada neste evento até o momento.</p>
<?php endif; ?>
