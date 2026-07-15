<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
$todasCategorias = [];
foreach ($concursos as $concurso) {
    foreach (isset($categoriasPorConcurso[(int) $concurso['id']]) ? $categoriasPorConcurso[(int) $concurso['id']] : [] as $categoria) {
        $todasCategorias[] = ['id' => $categoria['id'], 'nome' => $categoria['nome'], 'concurso_nome' => $concurso['nome']];
    }
}

function usuarios_link_ordenar($rotulo, $coluna, $ordenar, $direcao, $filtroConcursoId, $filtroPerfil, $busca)
{
    $novaDirecao = ($ordenar === $coluna && $direcao === 'asc') ? 'desc' : 'asc';
    $params = array_filter([
        'concurso_id' => $filtroConcursoId,
        'perfil' => $filtroPerfil,
        'busca' => $busca,
        'ordenar' => $coluna,
        'direcao' => $novaDirecao,
    ], function ($valor) {
        return $valor !== null && $valor !== '';
    });

    $seta = '';
    if ($ordenar === $coluna) {
        $seta = $direcao === 'asc' ? ' ▲' : ' ▼';
    }

    return '<a href="' . config('base_path') . '/index.php?' . htmlspecialchars(http_build_query($params + ['r' => 'usuarios/index']), ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8') . $seta . '</a>';
}
?>
<div class="pagina-titulo-acoes">
    <h1>Usuários</h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('usuarios/convidar'); ?>" class="btn-acao">+ Convidar usuário</a>
        <a href="<?php echo url('home/administrativo'); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<?php if (!empty($flash)): ?>
    <p style="color:green;"><?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<div class="filtros-barra-wrapper">
    <form method="get" action="<?php echo config('base_path'); ?>/index.php" class="filtros-barra">
        <input type="hidden" name="r" value="usuarios/index">
        <label class="filtro-busca">Busca:
            <input type="text" name="busca" placeholder="Nome, e-mail, status, perfil, acesso..." value="<?php echo htmlspecialchars((string) $busca, ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <label>Concurso:
            <select name="concurso_id">
                <option value="">Todos</option>
                <?php foreach ($concursos as $concurso): ?>
                    <option value="<?php echo (int) $concurso['id']; ?>" <?php echo $filtroConcursoId === (int) $concurso['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Perfil:
            <select name="perfil">
                <option value="">Todos</option>
                <?php foreach ($perfis as $perfil): ?>
                    <option value="<?php echo htmlspecialchars($perfil['chave'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $filtroPerfil === $perfil['chave'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($perfil['nome_exibicao'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="filtros-barra-acoes">
            <button type="submit" class="btn-icone" title="Filtrar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                </svg>
            </button>
            <a href="<?php echo url('usuarios/index'); ?>" class="btn-icone" title="Limpar filtros">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="1 4 1 10 7 10"></polyline>
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                </svg>
            </a>
        </div>
    </form>
</div>

<?php if (empty($usuarios)): ?>
    <p>Nenhum usuário encontrado.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr>
            <th><?php echo usuarios_link_ordenar('Nome', 'nome', $ordenar, $direcao, $filtroConcursoId, $filtroPerfil, $busca); ?></th>
            <th><?php echo usuarios_link_ordenar('E-mail', 'email', $ordenar, $direcao, $filtroConcursoId, $filtroPerfil, $busca); ?></th>
            <th><?php echo usuarios_link_ordenar('Status', 'status', $ordenar, $direcao, $filtroConcursoId, $filtroPerfil, $busca); ?></th>
            <th><?php echo usuarios_link_ordenar('Perfis', 'perfis', $ordenar, $direcao, $filtroConcursoId, $filtroPerfil, $busca); ?></th>
            <th><?php echo usuarios_link_ordenar('Acesso', 'acesso', $ordenar, $direcao, $filtroConcursoId, $filtroPerfil, $busca); ?></th>
            <th>Ações</th>
        </tr>
        <?php foreach ($usuarios as $usuario): ?>
        <tr>
            <td><?php echo htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <?php echo htmlspecialchars(ucfirst($usuario['status']), ENT_QUOTES, 'UTF-8'); ?>
                <?php if (!$usuario['ativo']): ?>
                    <br><strong>Suspenso</strong>
                <?php endif; ?>
            </td>
            <td>
                <?php if (empty($usuario['perfis'])): ?>
                    —
                <?php else: ?>
                    <?php foreach ($usuario['perfis'] as $vinculo): ?>
                        <?php echo htmlspecialchars($vinculo['perfil_nome'], ENT_QUOTES, 'UTF-8'); ?>
                        (<?php echo htmlspecialchars($vinculo['concurso_nome'] !== null ? $vinculo['concurso_nome'] : 'Global', ENT_QUOTES, 'UTF-8'); ?>)
                        <?php if ($vinculo['perfil'] === 'avaliador' && $vinculo['concurso_id'] !== null): ?>
                            <?php if (!empty($vinculo['categoria_atual'])): ?>
                                — Categoria: <?php echo htmlspecialchars($vinculo['categoria_atual']['categoria_nome'], ENT_QUOTES, 'UTF-8'); ?>
                            <?php else: ?>
                                — <em>sem categoria</em>
                            <?php endif; ?>
                        <?php endif; ?>
                        <br>
                    <?php endforeach; ?>
                <?php endif; ?>
            </td>
            <td>
                <?php
                    $tiposAcesso = [];
                    if ($usuario['senha_hash'] !== null) {
                        $tiposAcesso[] = 'Senha';
                    }
                    if ($usuario['google_id'] !== null) {
                        $tiposAcesso[] = 'Google';
                    }
                    if (!empty($tiposAcesso)) {
                        echo htmlspecialchars(implode(' + ', $tiposAcesso), ENT_QUOTES, 'UTF-8');
                    } else {
                        echo '<span class="status-pill laranja">Nenhum ainda</span>';
                    }
                ?>
            </td>
            <td>
                <?php if ($usuario['status'] === 'pendente'): ?>
                    <form method="post" action="<?php echo url('usuarios/aprovar'); ?>" style="display:inline;">
                        <input type="hidden" name="id" value="<?php echo (int) $usuario['id']; ?>">
                        <select name="perfil" id="campo-perfil-aprovar-<?php echo (int) $usuario['id']; ?>" required>
                            <option value="">Perfil...</option>
                            <?php foreach ($perfis as $perfil): ?>
                                <option value="<?php echo htmlspecialchars($perfil['chave'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($perfil['nome_exibicao'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="concurso_id">
                            <option value="">Global (todos os concursos)</option>
                            <?php foreach ($concursos as $concurso): ?>
                                <option value="<?php echo (int) $concurso['id']; ?>">
                                    <?php echo htmlspecialchars($concurso['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span id="campo-categoria-wrapper-<?php echo (int) $usuario['id']; ?>">
                            <select name="categoria_avaliador_id" title="Categoria de avaliador (precisa bater com o concurso escolhido acima)">
                                <option value="">Sem categoria de avaliador</option>
                                <?php foreach ($todasCategorias as $categoria): ?>
                                    <option value="<?php echo (int) $categoria['id']; ?>">
                                        <?php echo htmlspecialchars($categoria['nome'] . ' — ' . $categoria['concurso_nome'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </span>
                        <button type="submit" class="btn-icone" title="Aprovar">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </button>
                    </form>
                    <script>
                    (function () {
                        var select = document.getElementById('campo-perfil-aprovar-<?php echo (int) $usuario['id']; ?>');
                        var wrapper = document.getElementById('campo-categoria-wrapper-<?php echo (int) $usuario['id']; ?>');

                        function atualizar() {
                            wrapper.style.display = select.value === 'avaliador' ? '' : 'none';
                        }

                        select.addEventListener('change', atualizar);
                        atualizar();
                    })();
                    </script>
                    <form method="post" action="<?php echo url('usuarios/rejeitar'); ?>" style="display:inline;">
                        <input type="hidden" name="id" value="<?php echo (int) $usuario['id']; ?>">
                        <button type="submit" class="btn-icone" title="Rejeitar">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                        </button>
                    </form>
                <?php endif; ?>

                <div class="acoes-icones">
                    <a href="<?php echo url('usuarios/editar/' . (int) $usuario['id']); ?>" class="btn-icone" title="Editar usuário">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </a>
                    <?php if ($usuario['senha_hash'] === null && $usuario['google_id'] === null): ?>
                        <form method="post" action="<?php echo url('usuarios/reenviarConvite'); ?>">
                            <input type="hidden" name="id" value="<?php echo (int) $usuario['id']; ?>">
                            <button type="submit" class="btn-icone" title="Reenviar convite" onclick="return confirm('Reenviar o convite de acesso para <?php echo htmlspecialchars(addslashes($usuario['email']), ENT_QUOTES, 'UTF-8'); ?>? O link anterior deixará de funcionar.');">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <line x1="22" y1="2" x2="11" y2="13"></line>
                                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                </svg>
                            </button>
                        </form>
                    <?php endif; ?>
                    <?php if ($usuario['ativo']): ?>
                        <form method="post" action="<?php echo url('usuarios/suspender'); ?>">
                            <input type="hidden" name="id" value="<?php echo (int) $usuario['id']; ?>">
                            <button type="submit" class="btn-icone" title="Suspender" onclick="return confirm('Suspender este usuário? Ele não conseguirá mais fazer login.');">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                                </svg>
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="post" action="<?php echo url('usuarios/reativar'); ?>">
                            <input type="hidden" name="id" value="<?php echo (int) $usuario['id']; ?>">
                            <button type="submit" class="btn-icone" title="Reativar">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
