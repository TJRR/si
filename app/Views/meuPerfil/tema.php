<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<h1>Aparência</h1>

<section class="admin-card perfil-card">
    <p class="perfil-dica">Escolha um tema de cores. Vale para todo o sistema, inclusive o aplicativo de Evento. Quem não escolher nenhum vê o tema padrão definido pelo Administrador.</p>

    <form method="post" action="<?php echo url('meuPerfil/tema'); ?>"><?= campoCsrf() ?>
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
            <a href="<?php echo url($destinoPainel); ?>" class="btn-voltar">Voltar</a>
        </div>
    </form>
</section>
