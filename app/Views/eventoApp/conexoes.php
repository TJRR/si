<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 55: lista das conexoes do participante. O controlador ja passou cada
 * pessoa por PerfilVisibilidadeService, entao aqui so' aparece o que ela
 * mesma liberou - esta view nunca decide visibilidade.
 *
 * Os enderecos de rede social vem validados por linkHttpValido() no servico
 * e saem escapados, em aba nova e sem repassar a pagina de origem, no mesmo
 * padrao dos demais enderecos digitados por pessoa (Fase 49).
 */
?>

<div class="site-page">
    <?php
    $eventoId = $evento['id'];
    $tituloTopo = $evento['nome'];
    $urlVoltar = url('eventoApp/index/' . (int) $eventoId);
    require __DIR__ . '/_app_bar.php';
    ?>

    <div class="site-form-page">
        <?php require __DIR__ . '/_ajuda_card.php'; ?>
        <h2>Minhas conexões</h2>

        <?php if ((int) $config['ativo'] === 1): ?>
            <p>
                <a href="<?php echo url('eventoApp/ler/' . (int) $evento['id']); ?>" class="btn">Conectar com participante</a>
            </p>
        <?php endif; ?>

        <div class="admin-card">
            <p>
                <strong>Seus pontos em conexões: <?php echo (int) $resumo['total_pontos']; ?></strong>
                <br>
                <?php echo (int) $resumo['total_conexoes']; ?>
                <?php echo (int) $resumo['total_conexoes'] === 1 ? 'pessoa conectada' : 'pessoas conectadas'; ?>
                <?php if ((int) $config['teto_conexoes_pontuadas'] > 0): ?>
                    <br>
                    <small>
                        <?php echo (int) $resumo['total_pontuadas']; ?> de
                        <?php echo (int) $config['teto_conexoes_pontuadas']; ?> conexões que pontuam neste evento
                    </small>
                <?php endif; ?>
            </p>
        </div>

        <?php if (empty($conexoes)): ?>
            <p>Você ainda não se conectou com ninguém neste evento.</p>
        <?php endif; ?>

        <?php foreach ($conexoes as $conexao): ?>
            <?php $pessoa = $conexao['pessoa']; ?>
            <div class="admin-card">
                <?php if ($pessoa['foto_path'] !== null): ?>
                    <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $pessoa['foto_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars('Foto de ' . $pessoa['nome'], ENT_QUOTES, 'UTF-8'); ?>" style="display:block;width:72px;height:72px;object-fit:cover;border-radius:50%;margin:0 0 8px;" loading="lazy">
                <?php endif; ?>

                <p>
                    <strong><?php echo htmlspecialchars($pessoa['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    <?php if ($pessoa['cargo'] !== null): ?>
                        <br><small><?php echo htmlspecialchars($pessoa['cargo'], ENT_QUOTES, 'UTF-8'); ?></small>
                    <?php endif; ?>
                    <?php if ($pessoa['orgao_origem'] !== null): ?>
                        <br><small><?php echo htmlspecialchars($pessoa['orgao_origem'], ENT_QUOTES, 'UTF-8'); ?></small>
                    <?php endif; ?>
                </p>

                <p>
                    <a href="<?php echo htmlspecialchars('mailto:' . $pessoa['email'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($pessoa['email'], ENT_QUOTES, 'UTF-8'); ?></a>
                </p>

                <?php if ($pessoa['telefone'] !== null): ?>
                    <p>
                        <?php if ($pessoa['telefone_discagem'] !== null): ?>
                            <a href="<?php echo htmlspecialchars($pessoa['telefone_discagem'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($pessoa['telefone'], ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php else: ?>
                            <?php echo htmlspecialchars($pessoa['telefone'], ENT_QUOTES, 'UTF-8'); ?>
                        <?php endif; ?>
                        <?php if ($pessoa['telefone_whatsapp'] !== null): ?>
                            <br>
                            <a href="<?php echo htmlspecialchars($pessoa['telefone_whatsapp'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Conversar no WhatsApp</a>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>

                <?php if (!empty($pessoa['redes_sociais'])): ?>
                    <p>
                        <?php foreach ($pessoa['redes_sociais'] as $rede => $dadosRede): ?>
                            <a href="<?php echo htmlspecialchars($dadosRede['endereco'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo htmlspecialchars($dadosRede['rotulo'], ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars($dadosRede['rotulo'] . ' de ' . $pessoa['nome'], ENT_QUOTES, 'UTF-8'); ?>" style="display:inline-block;margin:0 8px 0 0;"><?php include __DIR__ . '/../_icone_rede_social.php'; ?></a>
                        <?php endforeach; ?>
                    </p>
                <?php endif; ?>

                <?php if ($pessoa['minicurriculo'] !== null): ?>
                    <p><small><?php echo nl2br(htmlspecialchars($pessoa['minicurriculo'], ENT_QUOTES, 'UTF-8')); ?></small></p>
                <?php endif; ?>

                <p>
                    <?php if ((int) $conexao['pontos_creditados'] > 0): ?>
                        <span class="status-pill verde">
                            <?php echo (int) $conexao['pontos_creditados']; ?>
                            <?php echo (int) $conexao['pontos_creditados'] === 1 ? 'ponto' : 'pontos'; ?>
                        </span>
                    <?php endif; ?>
                    <br>
                    <small>Conexão em <?php echo htmlspecialchars(formatarDataHora($conexao['conectado_em']), ENT_QUOTES, 'UTF-8'); ?></small>
                </p>
            </div>
        <?php endforeach; ?>

        <p>
            <small>
                Cada pessoa escolhe em Meu Perfil o que mostra para quem se conecta com ela. Nome e endereço
                de correio eletrônico aparecem sempre; os demais dados só quando ela autoriza.
            </small>
        </p>
    </div>
</div>
