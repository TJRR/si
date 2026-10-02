<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php $ehEdicao = $bonus !== null && isset($bonus['id']); ?>
<h1><?php echo $ehEdicao ? 'Editar bônus' : 'Novo bônus'; ?>: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>

<?php if ($temCredito): ?>
    <p class="status-pill laranja">
        Este bônus já concedeu pontos: o tipo não pode mais ser trocado, porque os créditos existentes
        passariam a valer por outra regra. A exigência e os pontos podem mudar, e a mudança não é retroativa.
    </p>
<?php endif; ?>

<form method="post" action="<?php echo $ehEdicao ? url('bonus/editar/' . (int) $evento['id'] . '/' . (int) $bonus['id']) : url('bonus/novo/' . (int) $evento['id']); ?>"><?= campoCsrf() ?>
    <label>Nome do bônus:
        <input type="text" name="nome" required maxlength="150" size="50" value="<?php echo htmlspecialchars((string) $dados['nome'], ENT_QUOTES, 'UTF-8'); ?>">
    </label>
    <?php if (isset($erros['nome'])): ?><p style="color:red;"><?php echo htmlspecialchars($erros['nome'], ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
    <p style="color:#555;font-size:0.9em;">É este nome que o participante vê no aplicativo e na mensagem de quando o bônus fecha.</p>

    <label>Descrição (opcional):
        <input type="text" name="descricao" maxlength="300" size="60" value="<?php echo htmlspecialchars((string) $dados['descricao'], ENT_QUOTES, 'UTF-8'); ?>">
    </label>
    <p style="color:#555;font-size:0.9em;">Uma frase explicando o que a pessoa precisa fazer. Aparece abaixo do nome, no painel dela.</p>

    <label>Tipo:
        <select name="tipo" id="bonus-tipo" <?php echo $temCredito ? 'disabled' : ''; ?>>
            <?php foreach ($tipos as $valor => $regra): ?>
                <option value="<?php echo htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); ?>"
                        data-pede-numero="<?php echo $regra['pede_numero'] ? '1' : '0'; ?>"
                        data-pede-tipo-atividade="<?php echo $regra['pede_tipo_atividade'] ? '1' : '0'; ?>"
                        data-pede-campos-perfil="<?php echo $regra['pede_campos_perfil'] ? '1' : '0'; ?>"
                        <?php echo $dados['tipo'] === $valor ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($regra['rotulo'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <?php if ($temCredito): ?>
        <input type="hidden" name="tipo" value="<?php echo htmlspecialchars((string) $dados['tipo'], ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <?php if (isset($erros['tipo'])): ?><p style="color:red;"><?php echo htmlspecialchars($erros['tipo'], ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
    <p style="color:#555;font-size:0.9em;">
        O tipo é a forma de apurar, e o sistema só apura as que estão nesta lista. O nome do bônus é livre:
        "Bingo da Inovação" é um bônus do tipo "atividades diferentes com presença", exigindo cinco.
    </p>
    <p style="color:#555;font-size:0.9em;">
        Os quatro últimos tipos são ações do participante, apuradas uma vez por pessoa e também para quem
        já as tinha feito antes de o bônus existir: "ter inscrição no evento" (criar conta), "preencher campos
        do perfil", "confirmar o credenciamento no local" (código de Gamificação, Credenciamento) e "ser autor
        de trabalho submetido" (vale para autor principal e coautores inscritos; trabalho desclassificado tira
        o bônus). O de perfil, uma vez concedido, fica, mesmo que a pessoa apague a foto depois.
    </p>

    <div id="bonus-exigencia">
        <label>Quantas o bônus exige:
            <input type="number" name="exigencia" min="0" max="65535" value="<?php echo (int) $dados['exigencia']; ?>">
        </label>
        <?php if (isset($erros['exigencia'])): ?><p style="color:red;"><?php echo htmlspecialchars($erros['exigencia'], ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
        <p style="color:#555;font-size:0.9em;">
            O bônus fecha quando a pessoa chega a este número. Baixar o número depois credita na hora quem já
            tinha chegado lá; subir não tira o que já foi concedido.
        </p>
    </div>

    <div id="bonus-tipo-atividade">
        <label>Tipo de atividade que conta:
            <select name="tipo_atividade_id">
                <option value="0">Escolha o tipo</option>
                <?php foreach ($tiposAtividade as $tipoAtividade): ?>
                    <option value="<?php echo (int) $tipoAtividade['id']; ?>" <?php echo (int) $dados['tipo_atividade_id'] === (int) $tipoAtividade['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($tipoAtividade['nome'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php if (isset($erros['tipo_atividade_id'])): ?><p style="color:red;"><?php echo htmlspecialchars($erros['tipo_atividade_id'], ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
        <?php if ($atividadesSemTipo > 0): ?>
        <p style="color:#555;font-size:0.9em;">
            Atenção: <?php echo (int) $atividadesSemTipo; ?>
            <?php echo (int) $atividadesSemTipo === 1 ? 'atividade deste evento está sem tipo cadastrado e nunca contará' : 'atividades deste evento estão sem tipo cadastrado e nunca contarão'; ?>
            para um bônus por tipo.
        </p>
        <?php endif; ?>
    </div>

    <?php
    $camposMarcados = !empty($dados['campos_perfil']) ? explode(',', (string) $dados['campos_perfil']) : [];
    ?>
    <div id="bonus-campos-perfil">
        <p><strong>Campos do perfil que o bônus exige</strong></p>
        <?php foreach (\App\Services\BonusApuracaoService::CAMPOS_PERFIL_ROTULOS as $campo => $rotuloCampo): ?>
            <label>
                <input type="checkbox" name="campos_perfil[]" value="<?php echo htmlspecialchars($campo, ENT_QUOTES, 'UTF-8'); ?>" <?php echo in_array($campo, $camposMarcados, true) ? 'checked' : ''; ?>>
                <?php echo htmlspecialchars($rotuloCampo, ENT_QUOTES, 'UTF-8'); ?>
            </label><br>
        <?php endforeach; ?>
        <?php if (isset($erros['campos_perfil'])): ?><p style="color:red;"><?php echo htmlspecialchars($erros['campos_perfil'], ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
        <p style="color:#555;font-size:0.9em;">
            O bônus fecha quando todos os campos marcados estão preenchidos no perfil da pessoa. Exemplo: "Completar
            perfil" com Foto, Cargo e Órgão de origem, e "Contato e minicurrículo" com Telefone e Minicurrículo.
            A conferência acontece quando a pessoa abre o painel ou grava o perfil no aplicativo.
        </p>
    </div>

    <label>Pontos:
        <input type="number" name="pontos" min="0" max="65535" value="<?php echo (int) $dados['pontos']; ?>">
    </label>
    <p style="color:#555;font-size:0.9em;">
        Quanto a pessoa ganha quando o bônus fecha. O valor é congelado no momento da concessão: mudar aqui
        vale só para quem fechar daqui em diante.
    </p>

    <label>
        <input type="checkbox" name="ativo" value="1" <?php echo (int) $dados['ativo'] === 1 ? 'checked' : ''; ?>>
        Bônus ativo
    </label>
    <p style="color:#555;font-size:0.9em;">
        Bônus desativado some do aplicativo e deixa de ser apurado. Os créditos já concedidos continuam valendo.
    </p>

    <div class="form-acoes">
        <a href="<?php echo url('bonus/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
        <button type="submit">Salvar</button>
    </div>
</form>

<script>
(function () {
    var selectTipo = document.getElementById('bonus-tipo');
    var blocoExigencia = document.getElementById('bonus-exigencia');
    var blocoTipoAtividade = document.getElementById('bonus-tipo-atividade');
    var blocoCamposPerfil = document.getElementById('bonus-campos-perfil');

    function ajustar() {
        var opcao = selectTipo.options[selectTipo.selectedIndex];
        blocoExigencia.style.display = opcao.getAttribute('data-pede-numero') === '1' ? 'block' : 'none';
        blocoTipoAtividade.style.display = opcao.getAttribute('data-pede-tipo-atividade') === '1' ? 'block' : 'none';
        blocoCamposPerfil.style.display = opcao.getAttribute('data-pede-campos-perfil') === '1' ? 'block' : 'none';
    }

    selectTipo.addEventListener('change', ajustar);
    ajustar();
})();
</script>
