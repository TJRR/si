<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
$ehEdicao = $tema !== null;
$corPrimariaInicio = $ehEdicao ? $tema['cor_primaria_inicio'] : '#F5761A';
$corSecundaria = $ehEdicao ? $tema['cor_secundaria'] : '#FFA451';
$corTerciaria = $ehEdicao ? $tema['cor_terciaria'] : '#C25400';
$corDestaqueApp = $ehEdicao ? $tema['cor_destaque_app'] : '#FFF4EA';
$nomeAtual = $ehEdicao ? $tema['nome'] : '';
$rotaForm = $ehEdicao ? 'tema/editar/' . $tema['id'] : 'tema/novo';
?>
<div class="pagina-titulo-acoes">
    <h1><?php echo $ehEdicao ? 'Editar tema' : 'Novo tema'; ?></h1>
    <div class="pagina-titulo-botoes">
        <button type="submit" form="form-tema">Salvar</button>
        <a href="<?php echo url('tema/index'); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<?php if (!empty($_SESSION['flash'])): ?>
    <p class="flash-mensagem <?php echo classeFlash(); ?>"><?php echo htmlspecialchars($_SESSION['flash'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['flash']); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo url($rotaForm); ?>" enctype="multipart/form-data" id="form-tema"><?= campoCsrf() ?>
    <fieldset>
        <legend>Identificação</legend>
        <p>
            <label>
                Nome do tema<br>
                <input type="text" name="nome" maxlength="60" required value="<?php echo htmlspecialchars($nomeAtual, ENT_QUOTES, 'UTF-8'); ?>">
            </label>
        </p>
    </fieldset>

    <fieldset>
        <legend>Cores</legend>
        <p>
            <?php $nome = 'cor_primaria_inicio'; $valor = $corPrimariaInicio; $rotulo = 'Primária'; $permiteVazio = false; ?>
            <?php include __DIR__ . '/../_campo_cor.php'; ?>
        </p>
        <small>Topo do degradê de fundo do aplicativo e cor de destaque dos botões do sistema.</small>
        <p>
            <?php $nome = 'cor_secundaria'; $valor = $corSecundaria; $rotulo = 'Secundária'; $permiteVazio = false; ?>
            <?php include __DIR__ . '/../_campo_cor.php'; ?>
        </p>
        <small>Cor do botão de ação principal dentro do aplicativo.</small>
        <p>
            <?php $nome = 'cor_terciaria'; $valor = $corTerciaria; $rotulo = 'Terciária'; $permiteVazio = false; ?>
            <?php include __DIR__ . '/../_campo_cor.php'; ?>
        </p>
        <small>Base do degradê de fundo do aplicativo e acento dos avisos.</small>
        <p>
            <?php $nome = 'cor_destaque_app'; $valor = $corDestaqueApp; $rotulo = 'Fundo claro'; $permiteVazio = false; ?>
            <?php include __DIR__ . '/../_campo_cor.php'; ?>
        </p>
        <small>Fundo dos cartões, do menu e da área de conteúdo do aplicativo; escolha um tom claro o suficiente para contrastar com texto escuro.</small>
    </fieldset>

    <fieldset>
        <legend>Logos</legend>
        <p>
            <label>
                Logo do Concurso (portal público e painel administrativo):<br>
                <?php if ($ehEdicao && !empty($tema['logo_concurso_path'])): ?>
                    <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $tema['logo_concurso_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="Logo de Concurso atual" style="max-width:200px;display:block;margin-bottom:.5rem;">
                <?php endif; ?>
                <input type="file" name="logo_concurso" accept="image/*">
            </label>
        </p>
        <p>
            <label>
                Logo do Evento (tela de entrada do aplicativo do Evento):<br>
                <?php if ($ehEdicao && !empty($tema['logo_evento_path'])): ?>
                    <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $tema['logo_evento_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="Logo de Evento atual" style="max-width:200px;display:block;margin-bottom:.5rem;">
                <?php endif; ?>
                <input type="file" name="logo_evento" accept="image/*">
            </label>
        </p>
        <small>Opcionais: sem logo enviada, o sistema usa a imagem padrão.</small>
    </fieldset>
</form>
