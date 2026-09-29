<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 55 (pendencia 18): selecao de tema visual sem sair do aplicativo do
 * Evento. Mesma lista de temas publicados da tela administrativa; o tema
 * escolhido vale em todo o sistema.
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
        <h2>Aparência</h2>
        <?php require __DIR__ . '/_abas_perfil.php'; ?>

        <div class="admin-card">
            <p>
                <small>
                    Escolha um tema de cores. Vale para todo o sistema, inclusive fora do aplicativo. Quem não
                    escolher nenhum vê o tema padrão definido pela organização.
                </small>
            </p>

            <form method="post" action="<?php echo url('eventoAppPerfil/aparencia/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
                <div class="tema-escolha-lista">
                    <?php foreach ($temasDisponiveis as $temaOpcao): ?>
                        <label class="tema-escolha-item">
                            <input type="radio" name="tema_id" value="<?php echo (int) $temaOpcao['id']; ?>" <?php echo ((int) $temaAtualId === (int) $temaOpcao['id']) ? 'checked' : ''; ?>>
                            <span class="tema-amostra-degrade" style="--tema-amostra-inicio:<?php echo htmlspecialchars($temaOpcao['cor_primaria_inicio'], ENT_QUOTES, 'UTF-8'); ?>;--tema-amostra-fim:<?php echo htmlspecialchars($temaOpcao['cor_terciaria'], ENT_QUOTES, 'UTF-8'); ?>;"></span>
                            <?php echo htmlspecialchars($temaOpcao['nome'], ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="form-acoes">
                    <button type="submit">Salvar tema</button>
                    <a href="<?php echo url('eventoApp/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
                </div>
            </form>
        </div>
    </div>
</div>
