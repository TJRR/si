<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 55: "Meus dados e o que compartilho", dentro do aplicativo do Evento.
 * Mesma tabela de dados da pessoa que a tela administrativa "Meu perfil"
 * usa (usuarios_perfil), com os campos novos da fase (telefone, redes
 * sociais) e as seis marcas que decidem o que outro participante ve depois
 * de uma conexao.
 *
 * Em caso de erro, $valores traz o que a pessoa acabou de digitar, para
 * nada se perder; fora disso, os campos vem do que esta gravado.
 */
$perfilAtual = isset($perfil) ? $perfil : null;
$marcasVisibilidade = \App\Repositories\UsuarioPerfilRepository::MARCAS_VISIBILIDADE;
$redesSuportadas = \App\Repositories\UsuarioPerfilRepository::REDES_SUPORTADAS;
$rotulosRedes = \App\Repositories\UsuarioPerfilRepository::REDES_ROTULOS;
$enderecoRedes = \App\Repositories\UsuarioPerfilRepository::REDES_ENDERECO;
$redesGravadas = (new \App\Repositories\UsuarioPerfilRepository())->redesSociais($perfilAtual);

$valorDoCampo = function ($chave) use ($valores, $perfilAtual) {
    if (is_array($valores) && array_key_exists($chave, $valores)) {
        return (string) $valores[$chave];
    }

    return $perfilAtual !== null && $perfilAtual[$chave] !== null ? (string) $perfilAtual[$chave] : '';
};

$marcaLigada = function ($chave) use ($valores, $perfilAtual) {
    if (is_array($valores) && array_key_exists($chave, $valores)) {
        return !empty($valores[$chave]);
    }

    return $perfilAtual !== null && !empty($perfilAtual[$chave]);
};

$enderecoDaRede = function ($rede) use ($valores, $redesGravadas) {
    if (is_array($valores) && isset($valores['redes_sociais'][$rede])) {
        return (string) $valores['redes_sociais'][$rede];
    }

    return isset($redesGravadas[$rede]) ? $redesGravadas[$rede] : '';
};

$rotulosMarcas = [
    'mostrar_foto' => 'Minha foto',
    'mostrar_cargo' => 'Meu cargo',
    'mostrar_orgao_origem' => 'Meu órgão de origem',
    'mostrar_minicurriculo' => 'Meu minicurrículo',
    'mostrar_telefone' => 'Meu telefone',
    'mostrar_redes_sociais' => 'Minhas redes sociais',
];
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
        <h2>Meu Perfil</h2>
        <?php require __DIR__ . '/_abas_perfil.php'; ?>

        <form method="post" action="<?php echo url('eventoAppPerfil/index/' . (int) $evento['id']); ?>" enctype="multipart/form-data"><?= campoCsrf() ?>
            <div class="admin-card">
                <?php if (!empty($usuario['foto_path'])): ?>
                    <img src="<?php echo htmlspecialchars(config('base_path') . '/assets/' . $usuario['foto_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="Minha foto" style="display:block;width:96px;height:96px;object-fit:cover;border-radius:50%;margin:0 0 8px;">
                <?php endif; ?>

                <label>Foto
                    <input type="file" name="foto" accept="image/*">
                </label>
                <p><small>JPG, PNG, WEBP ou GIF, até 4 MB.</small></p>

                <label>Nome completo
                    <input type="text" name="nome" value="<?php echo htmlspecialchars(is_array($valores) ? $valores['nome'] : $usuario['nome'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </label>

                <label>Endereço de correio eletrônico
                    <input type="text" value="<?php echo htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8'); ?>" disabled>
                </label>
                <p><small>O endereço de correio eletrônico não pode ser alterado.</small></p>
            </div>

            <div class="admin-card">
                <h3>Meus dados</h3>

                <label>Tipo de documento
                    <select name="tipo_documento">
                        <?php foreach (\App\Repositories\UsuarioPerfilRepository::TIPOS_DOCUMENTO as $tipo): ?>
                            <option value="<?php echo htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $valorDoCampo('tipo_documento') === $tipo ? 'selected' : ''; ?>><?php echo htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>Documento
                    <input type="text" name="documento" value="<?php echo htmlspecialchars($valorDoCampo('documento'), ENT_QUOTES, 'UTF-8'); ?>">
                </label>

                <label>Cargo
                    <input type="text" name="cargo" maxlength="150" value="<?php echo htmlspecialchars($valorDoCampo('cargo'), ENT_QUOTES, 'UTF-8'); ?>">
                </label>

                <label>Categoria profissional
                    <select name="categoria_profissional">
                        <option value="">Selecione</option>
                        <?php foreach (\App\Repositories\UsuarioPerfilRepository::CATEGORIAS_PROFISSIONAIS as $categoria): ?>
                            <option value="<?php echo htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $valorDoCampo('categoria_profissional') === $categoria ? 'selected' : ''; ?>><?php echo htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>Tribunal ou outro órgão de origem
                    <input type="text" name="orgao_origem" maxlength="150" value="<?php echo htmlspecialchars($valorDoCampo('orgao_origem'), ENT_QUOTES, 'UTF-8'); ?>">
                </label>

                <label>Minicurrículo
                    <textarea name="minicurriculo" rows="4"><?php echo htmlspecialchars($valorDoCampo('minicurriculo'), ENT_QUOTES, 'UTF-8'); ?></textarea>
                </label>
            </div>

            <div class="admin-card">
                <h3>Meus contatos</h3>

                <label>Telefone
                    <input type="text" name="telefone" class="campo-telefone" maxlength="20" value="<?php echo htmlspecialchars($valorDoCampo('telefone'), ENT_QUOTES, 'UTF-8'); ?>">
                </label>

                <label>
                    <input type="checkbox" name="telefone_whatsapp" value="1" <?php echo $marcaLigada('telefone_whatsapp') ? 'checked' : ''; ?>>
                    Este número recebe mensagem no WhatsApp
                </label>

                <?php foreach ($redesSuportadas as $rede): ?>
                    <?php
                    /* Correção pedida pelo dono: a pessoa informa só o nome de
                    usuário e o sistema monta o endereço. O começo do endereço
                    fica à mostra, ao lado do rótulo, para ela saber o que será
                    montado; colar o endereço inteiro continua funcionando. */
                    $prefixoRede = isset($enderecoRedes[$rede]['prefixo'])
                        ? preg_replace('#^https?://#', '', $enderecoRedes[$rede]['prefixo'])
                        : '';
                    ?>
                    <label><?php echo htmlspecialchars(isset($rotulosRedes[$rede]) ? $rotulosRedes[$rede] : $rede, ENT_QUOTES, 'UTF-8'); ?>
                        <?php if ($prefixoRede !== ''): ?>
                            <small><?php echo htmlspecialchars($prefixoRede, ENT_QUOTES, 'UTF-8'); ?></small>
                        <?php endif; ?>
                        <input type="text" name="rede_<?php echo htmlspecialchars($rede, ENT_QUOTES, 'UTF-8'); ?>" maxlength="255" placeholder="seu.usuario" value="<?php echo htmlspecialchars($enderecoDaRede($rede), ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                <?php endforeach; ?>
                <p><small>Informe apenas o seu nome de usuário em cada rede. Se preferir, cole o endereço completo do seu perfil: o sistema entende os dois jeitos e guarda sempre o endereço pronto para abrir.</small></p>
            </div>

            <div class="admin-card">
                <h3>O que compartilho nas conexões</h3>
                <p>
                    <small>
                        Quando você se conecta com outra pessoa no evento, ela vê seu nome e seu endereço de correio
                        eletrônico. Escolha abaixo o que mais quer mostrar. Você pode mudar isso quando quiser, e a
                        mudança vale também para as conexões que já aconteceram. Lembre-se de que esconder um dado
                        depois vale para o sistema, não para a memória de quem já viu.
                    </small>
                </p>

                <?php foreach ($marcasVisibilidade as $marca): ?>
                    <label>
                        <input type="checkbox" name="<?php echo htmlspecialchars($marca, ENT_QUOTES, 'UTF-8'); ?>" value="1" <?php echo $marcaLigada($marca) ? 'checked' : ''; ?>>
                        <?php echo htmlspecialchars(isset($rotulosMarcas[$marca]) ? $rotulosMarcas[$marca] : $marca, ENT_QUOTES, 'UTF-8'); ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="form-acoes">
                <button type="submit">Salvar</button>
                <a href="<?php echo url('eventoApp/index/' . (int) $evento['id']); ?>" class="btn-voltar">Voltar</a>
            </div>
        </form>
    </div>
</div>
