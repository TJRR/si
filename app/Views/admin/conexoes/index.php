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
    Cada participante lê no aplicativo o código de outro (na tela do aplicativo dele ou no crachá, quando o
    evento usa crachá) e as duas pessoas ficam conectadas, pontuando as duas com uma leitura só. A lista abaixo
    mostra os pares, para que uma leitura feita por engano possa ser desfeita aqui: remover a conexão tira os
    pontos dos dois lados. A classificação geral do evento fica em Gamificação.
</p>
<p style="color:#555;font-size:0.9em;">
    Quem se conectou com quem é dado pessoal dos dois participantes: use a lista só para conferir e corrigir.
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

<h2>Conexões registradas</h2>

<?php
$parametrosFiltro = array_filter($filtros, function ($valor) {
    return $valor !== '' && $valor !== null;
});
$podeAgir = $podeEditar && empty($gincanaEncerrada);
?>
<?php if (!empty($gincanaEncerrada)): ?>
    <p class="status-pill laranja">A gincana deste evento foi encerrada e a classificação está congelada: nenhuma conexão pode ser removida.</p>
<?php endif; ?>

<div class="filtros-barra-wrapper">
    <form method="get" action="<?php echo config('base_path'); ?>/index.php" class="filtros-barra">
        <input type="hidden" name="r" value="conexoes/index/<?php echo (int) $evento['id']; ?>">
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
            <a href="<?php echo url('conexoes/index/' . (int) $evento['id']); ?>" class="btn-icone" title="Limpar filtros">
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
        <?php echo count($conexoes); ?>
        <?php echo count($conexoes) === 1 ? 'conexão encontrada' : 'conexões encontradas'; ?>
    </span>
    <?php if ($podeEditar && !empty($conexoes)): ?>
        <a href="<?php echo htmlspecialchars(url('conexoes/exportar/' . (int) $evento['id']) . ($parametrosFiltro !== [] ? '&' . http_build_query($parametrosFiltro) : ''), ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Exportar a lista filtrada">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
        </a>
    <?php endif; ?>
</div>

<?php if (empty($conexoes)): ?>
    <p>Nenhuma conexão encontrada<?php echo $parametrosFiltro !== [] ? ' com estes filtros' : ' neste evento até o momento'; ?>.</p>
<?php else: ?>
    <?php if ($podeAgir): ?>
        <?php /* Formulário de lote vazio e fora da tabela: as caixas de cada linha
        se ligam a ele pelo atributo "form=", porque HTML não aceita formulário
        dentro de formulário. */ ?>
        <form method="post" id="form-acoes-em-massa" action="<?php echo url('conexoes/removerEmLote/' . (int) $evento['id']); ?>"
              onsubmit="return confirm('Remover as conexões selecionadas? Os pontos dos dois lados saem da classificação, e as pessoas podem se conectar de novo.');"><?= campoCsrf() ?>
            <input type="hidden" name="evento_id" value="<?php echo (int) $evento['id']; ?>">
        </form>
    <?php endif; ?>

    <div class="tabela-scroll">
        <table border="1" cellpadding="6">
            <tr>
                <?php if ($podeAgir): ?>
                    <th><input type="checkbox" id="marcar-todos"></th>
                <?php endif; ?>
                <th>Participante</th>
                <th>Pontos</th>
                <th>Participante</th>
                <th>Pontos</th>
                <th>Conectados em</th>
            </tr>
            <?php foreach ($conexoes as $conexao): ?>
                <tr>
                    <?php if ($podeAgir): ?>
                        <td><input type="checkbox" class="marcar-linha" name="conexao_ids[]" value="<?php echo (int) $conexao['id']; ?>" form="form-acoes-em-massa"></td>
                    <?php endif; ?>
                    <td>
                        <?php echo htmlspecialchars($conexao['nome_menor'], ENT_QUOTES, 'UTF-8'); ?>
                        <br><small><?php echo htmlspecialchars($conexao['email_menor'], ENT_QUOTES, 'UTF-8'); ?></small>
                    </td>
                    <td><?php echo (int) $conexao['pontos_creditados_menor']; ?></td>
                    <td>
                        <?php echo htmlspecialchars($conexao['nome_maior'], ENT_QUOTES, 'UTF-8'); ?>
                        <br><small><?php echo htmlspecialchars($conexao['email_maior'], ENT_QUOTES, 'UTF-8'); ?></small>
                    </td>
                    <td><?php echo (int) $conexao['pontos_creditados_maior']; ?></td>
                    <td><?php echo htmlspecialchars(formatarDataHora($conexao['conectado_em']), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <?php if ($podeAgir): ?>
        <p>Com as selecionadas:
            <span class="acoes-icones">
                <button type="submit" form="form-acoes-em-massa" class="btn-icone" title="Remover as conexões">
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
