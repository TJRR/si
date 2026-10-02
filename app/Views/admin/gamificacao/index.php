<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 58: classificação geral do evento, somada na hora de todas as
 * origens de pontos (GamificacaoService::classificacao()). A lista é
 * nominal: é a lista do balcão de entrega das mudas.
 */
$rotulosOrigem = [
    'presenca' => 'Presença',
    'competicoes' => 'Competições',
    'conexoes' => 'Conexões',
    'estandes' => 'Estandes',
    'divulgacao' => 'Divulgação',
    'bonus' => 'Bônus',
];
?>
<div class="pagina-titulo-acoes">
    <h1>Gamificação: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <?php if ($podeEditar && empty($gincanaEncerrada)): ?>
        <form method="post" action="<?php echo url('gamificacao/reconferir/' . (int) $evento['id']); ?>" style="display:inline;" onsubmit="return confirm('Reconferir a pontuação de todos os inscritos agora?');"><?= campoCsrf() ?>
            <button type="submit" class="btn-acao">Reconferir agora</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="admin-card">
    <p>
        <span class="status-pill <?php echo (int) $config['ativo'] === 1 ? 'verde' : 'laranja'; ?>">
            <?php echo (int) $config['ativo'] === 1 ? 'Gamificação ligada no aplicativo' : 'Gamificação desligada no aplicativo'; ?>
        </span>
        <span class="status-pill <?php echo (int) $config['classificacao_visivel'] === 1 ? 'verde' : 'laranja'; ?>">
            <?php if ((int) $config['classificacao_visivel'] === 1): ?>
                Inscritos veem os <?php echo (int) $config['classificacao_quantidade']; ?> primeiros
                <?php echo (int) $config['classificacao_mostrar_nomes'] === 1 ? 'com nome' : 'sem nome'; ?>
            <?php else: ?>
                Classificação escondida dos inscritos
            <?php endif; ?>
        </span>
        <?php if (!empty($gincanaEncerrada)): ?>
            <span class="status-pill vermelho">Gincana encerrada em <?php echo htmlspecialchars(formatarDataHora($config['encerramento_em']), ENT_QUOTES, 'UTF-8'); ?>: classificação congelada</span>
        <?php elseif (!empty($config['encerramento_em'])): ?>
            <span class="status-pill laranja">Encerramento agendado para <?php echo htmlspecialchars(formatarDataHora($config['encerramento_em']), ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>
    </p>
</div>

<p style="color:#555;font-size:0.9em;">
    A classificação soma, na hora, os pontos válidos de todas as origens: presença em atividade, competições,
    conexões, visitas a estandes, divulgação e bônus (que incluem as ações do participante). Só aparece quem tem
    ao menos um ponto. O empate no total é resolvido pela cascata da aba Desempate; esgotada a cascata, a posição
    é compartilhada e a linha é marcada. "Reconferir agora" cria o crédito que falta para presenças e ações já
    feitas, por exemplo depois de cadastrar pontos num tipo de atividade ou um bônus novo. Faça as reconferências
    antes do evento: um crédito criado por reconferência leva o instante dela, o que conta no critério "quem
    chegou primeiro".
</p>

<?php if ($indisponivel): ?>
    <p class="status-pill vermelho">A classificação está indisponível no momento. Tente de novo em alguns instantes.</p>
<?php else: ?>
    <?php $buscando = $filtros['busca'] !== ''; ?>
    <div class="filtros-barra-wrapper">
        <form method="get" action="<?php echo config('base_path'); ?>/index.php" class="filtros-barra">
            <input type="hidden" name="r" value="gamificacao/index/<?php echo (int) $evento['id']; ?>">
            <label class="filtro-busca">Busca:
                <input type="text" name="busca" placeholder="Nome ou correio eletrônico" value="<?php echo htmlspecialchars($filtros['busca'], ENT_QUOTES, 'UTF-8'); ?>">
            </label>
            <div class="filtros-barra-acoes">
                <button type="submit" class="btn-icone" title="Filtrar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                    </svg>
                </button>
                <a href="<?php echo url('gamificacao/index/' . (int) $evento['id']); ?>" class="btn-icone" title="Limpar filtros">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="1 4 1 10 7 10"></polyline>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                    </svg>
                </a>
            </div>
        </form>
    </div>

    <?php /* A posição continua sendo a do evento inteiro, mesmo com a busca
    aplicada: é o dado principal desta tela. */ ?>
    <div class="auditoria-resumo">
        <span>
            <?php echo count($classificacao); ?>
            <?php echo count($classificacao) === 1 ? 'pessoa encontrada' : 'pessoas encontradas'; ?>
            <?php if ($buscando): ?>
                de <?php echo (int) $totalClassificados; ?> classificadas
            <?php endif; ?>
        </span>
        <?php if ($podeEditar && !empty($classificacao)): ?>
            <a href="<?php echo htmlspecialchars(url('gamificacao/exportar/' . (int) $evento['id']) . ($buscando ? '&' . http_build_query(['busca' => $filtros['busca']]) : ''), ENT_QUOTES, 'UTF-8'); ?>" class="btn-icone" title="Exportar a classificação">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (!$indisponivel && empty($classificacao)): ?>
    <p>Ninguém pontuou neste evento<?php echo $filtros['busca'] !== '' ? ' com esta busca' : ' até o momento'; ?>.</p>
<?php elseif (!$indisponivel): ?>
    <div class="tabela-scroll">
    <table border="1" cellpadding="6">
        <thead>
            <tr>
                <th>Posição</th>
                <th>Nome</th>
                <th>Total</th>
                <?php foreach ($rotulosOrigem as $rotulo): ?>
                    <th><?php echo htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8'); ?></th>
                <?php endforeach; ?>
                <th>Último ponto</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($classificacao as $linha): ?>
                <tr>
                    <td>
                        <?php echo (int) $linha['posicao']; ?>
                        <?php if ($linha['empatado']): ?>
                            <span class="status-pill laranja">Empate</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($linha['nome'], ENT_QUOTES, 'UTF-8'); ?>
                        <br><small><?php echo htmlspecialchars($linha['email'], ENT_QUOTES, 'UTF-8'); ?></small>
                    </td>
                    <td><strong><?php echo (int) $linha['total']; ?></strong></td>
                    <?php foreach (array_keys($rotulosOrigem) as $origem): ?>
                        <td><?php echo (int) $linha['por_origem'][$origem]; ?></td>
                    <?php endforeach; ?>
                    <td><?php echo $linha['ultimo_ponto_em'] !== null ? htmlspecialchars(formatarDataHora($linha['ultimo_ponto_em']), ENT_QUOTES, 'UTF-8') : ''; ?></td>
                    <td>
                        <div class="acoes-icones">
                            <a href="<?php echo url('gamificacao/extrato/' . (int) $evento['id'] . '/' . (int) $linha['inscricao_id']); ?>" class="btn-icone" title="Extrato desta pessoa">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="8" y1="13" x2="16" y2="13"></line>
                                    <line x1="8" y1="17" x2="13" y2="17"></line>
                                </svg>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
<?php endif; ?>
