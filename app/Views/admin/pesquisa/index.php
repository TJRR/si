<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Pesquisa de satisfação: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <?php if ($podeEditar && $totalRespondentes > 0): ?>
        <a href="<?php echo url('pesquisa/exportarRespostas/' . (int) $evento['id']); ?>" class="btn-acao">Exportar respostas</a>
        <?php endif; ?>
    </div>
</div>

<?php if ((int) $config['ativo'] === 1): ?>
    <p class="status-pill verde">Pesquisa ativa<?php echo $janelaTexto !== '' ? ', de ' . htmlspecialchars($janelaTexto, ENT_QUOTES, 'UTF-8') : ''; ?></p>
<?php else: ?>
    <p class="status-pill laranja">Pesquisa desativada neste evento</p>
<?php endif; ?>

<?php if (!$temBonusDaPesquisa): ?>
    <p class="status-pill laranja">
        Responder não está creditando pontos: não há bônus ativo do tipo "responder à pesquisa de satisfação".
        Cadastre um em <a href="<?php echo url('bonus/index/' . (int) $evento['id']); ?>">Bônus</a>.
    </p>
<?php endif; ?>

<div class="admin-card">
    <p>
        <strong><?php echo (int) $totalRespondentes; ?></strong>
        <?php echo (int) $totalRespondentes === 1 ? 'pessoa respondeu' : 'pessoas responderam'; ?>
        de <strong><?php echo (int) $totalInscritos; ?></strong> inscritos.
    </p>
    <?php if (!empty($config['convite_enviado_em'])): ?>
        <p style="color:#555;">Último convite enviado em <?php echo htmlspecialchars(formatarDataHora($config['convite_enviado_em']), ENT_QUOTES, 'UTF-8'); ?>.</p>
    <?php endif; ?>
    <?php if ($podeEditar): ?>
    <form method="post" action="<?php echo url('pesquisa/convidar/' . (int) $evento['id']); ?>" onsubmit="return confirm('Convidar quem ainda não respondeu? O aviso no aplicativo é imediato e as mensagens por correio eletrônico saem dez por minuto.');"><?= campoCsrf() ?>
        <button type="submit">Convidar a responder</button>
    </form>
    <p style="color:#555;font-size:0.9em;">
        O convite vai só para quem ainda não respondeu, pelo sino do aplicativo e por correio eletrônico.
        As mensagens usam a mesma fila do evento, dez por minuto, então algumas centenas de inscritos levam
        perto de uma hora para receber.
    </p>
    <?php endif; ?>
</div>

<?php if (!$abrirNumeros): ?>
    <p style="color:#555;">
        Os números por pergunta aparecem a partir de <?php echo (int) $minimoParaExibir; ?> respostas.
        Com menos que isso, o conjunto é pequeno demais para preservar o anonimato de quem respondeu,
        sobretudo nas perguntas de texto livre.
    </p>
<?php else: ?>
    <?php foreach ($resultado as $item): ?>
    <div class="admin-card">
        <p>
            <strong><?php echo htmlspecialchars($item['enunciado'], ENT_QUOTES, 'UTF-8'); ?></strong>
            <?php if ((int) $item['ativa'] !== 1): ?>
                <span class="status-pill laranja">Desativada</span>
            <?php endif; ?>
            <br>
            <small><?php echo (int) $item['envios']; ?> <?php echo (int) $item['envios'] === 1 ? 'resposta' : 'respostas'; ?></small>
        </p>

        <?php if ($item['tipo'] === 'escala'): ?>
            <p>Média: <strong><?php echo $item['media'] !== null ? htmlspecialchars((string) $item['media'], ENT_QUOTES, 'UTF-8') : 'sem respostas'; ?></strong></p>
            <table>
                <tr><th>Nota</th><th>Respostas</th></tr>
                <?php for ($nota = 1; $nota <= 5; $nota++): ?>
                <tr>
                    <td><?php echo $nota; ?></td>
                    <td><?php echo isset($item['distribuicao'][$nota]) ? (int) $item['distribuicao'][$nota] : 0; ?></td>
                </tr>
                <?php endfor; ?>
            </table>
        <?php elseif ($item['tipo'] === 'texto'): ?>
            <?php if (empty($item['textos'])): ?>
                <p style="color:#555;">Nenhuma resposta escrita.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($item['textos'] as $texto): ?>
                    <li><?php echo nl2br(htmlspecialchars($texto, ENT_QUOTES, 'UTF-8')); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php else: ?>
            <table>
                <tr><th>Opção</th><th>Respostas</th><th>Percentual</th></tr>
                <?php foreach ($item['opcoes'] as $indice => $opcao): ?>
                <?php
                $posicao = $indice + 1;
                $quantas = isset($item['distribuicao'][$posicao]) ? (int) $item['distribuicao'][$posicao] : 0;
                $percentual = $item['envios'] > 0 ? round(($quantas / $item['envios']) * 100) : 0;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($opcao, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo $quantas; ?></td>
                    <td><?php echo $percentual; ?>%</td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<p style="color:#555;font-size:0.9em;">
    Esta tela nunca mostra quem respondeu o quê: as respostas são guardadas sem nenhuma ligação com a
    pessoa, e o sistema não tem como refazer esse elo.
</p>
