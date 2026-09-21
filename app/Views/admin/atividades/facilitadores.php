<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Facilitadores: <?php echo htmlspecialchars($atividade['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('atividades/editar/' . (int) $atividade['id']); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<h2>Vinculados</h2>
<?php if (empty($facilitadores)): ?>
    <p>Nenhum facilitador vinculado ainda.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Cargo</th><th>Ações</th></tr>
        <?php foreach ($facilitadores as $facilitador): ?>
        <tr>
            <td><?php echo htmlspecialchars($facilitador['usuario_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($facilitador['usuario_email'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($facilitador['perfil_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string) $facilitador['cargo'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <form method="post" action="<?php echo url('atividades/removerFacilitador'); ?>"><?= campoCsrf() ?>
                    <input type="hidden" name="id" value="<?php echo (int) $facilitador['id']; ?>">
                    <button type="submit" class="btn-icone" title="Remover">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<h2>Vincular novo facilitador</h2>
<p style="color:#555;font-size:0.9em;">Busque um usuário já cadastrado no sistema: nenhuma conta nova é criada por aqui.</p>

<?php if (empty($perfis)): ?>
    <p style="color:#a00;">Nenhum perfil cadastrado ainda para este evento. Cadastre ao menos um em <a href="<?php echo url('eventos/perfis/' . (int) $evento['id']); ?>">Eventos &gt; Perfis</a> antes de vincular um facilitador.</p>
<?php else: ?>
<form method="post" action="<?php echo url('atividades/vincularFacilitador/' . (int) $atividade['id']); ?>" enctype="multipart/form-data"><?= campoCsrf() ?>
    <div class="busca-usuario" data-busca-usuario data-endpoint="<?php echo url('atividades/buscarUsuarios'); ?>" data-perfil-endpoint="<?php echo url('atividades/perfilUsuario'); ?>">
        <label>Buscar usuário (nome ou e-mail):
            <input type="text" data-busca-usuario-input autocomplete="off" size="40" placeholder="Digite ao menos 2 caracteres">
        </label>
        <ul class="busca-usuario-resultados" hidden data-busca-usuario-resultados></ul>
        <p style="color:#555;font-size:0.9em;" data-busca-usuario-aviso hidden>Documento, cargo, categoria, órgão de origem, minicurrículo e foto pertencem à pessoa: se ela já tiver esses dados de uma designação anterior, foram preenchidos automaticamente abaixo.</p>
        <input type="hidden" name="usuario_id" required data-busca-usuario-id>
    </div><br>

    <label>Perfil:
        <select name="perfil_id" required>
            <?php foreach ($perfis as $perfil): ?>
                <option value="<?php echo (int) $perfil['id']; ?>"><?php echo htmlspecialchars($perfil['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
        </select>
    </label><br>

    <label>Tipo de documento:
        <select name="tipo_documento" data-campo-perfil="tipo_documento">
            <option value="CPF">CPF</option>
            <option value="RG">RG</option>
            <option value="RNE">RNE</option>
            <option value="Passaporte">Passaporte</option>
        </select>
    </label>
    <label>Documento:
        <input type="text" name="documento" required size="20" data-campo-perfil="documento">
    </label><br>

    <label>Cargo:
        <input type="text" name="cargo" maxlength="150" size="30" data-campo-perfil="cargo">
    </label><br>

    <label>Categoria:
        <select name="categoria" data-campo-perfil="categoria_profissional">
            <option value="">Selecione</option>
            <?php foreach (\App\Repositories\UsuarioPerfilRepository::CATEGORIAS_PROFISSIONAIS as $categoria): ?>
                <option value="<?php echo htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
        </select>
    </label><br>

    <label>Tribunal ou outro órgão de origem:
        <input type="text" name="orgao_origem" maxlength="150" size="40" data-campo-perfil="orgao_origem">
    </label><br>

    <label>Foto:
        <input type="file" name="foto" accept="image/*">
    </label>
    <span data-foto-atual></span><br>

    <fieldset>
        <legend>Minicurrículo</legend>
        <label>
            <textarea name="minicurriculo" rows="4" cols="60" data-campo-perfil="minicurriculo"></textarea>
        </label>
    </fieldset>

    <div class="form-acoes">
        <button type="submit">Vincular</button>
    </div>
</form>
<?php endif; ?>
