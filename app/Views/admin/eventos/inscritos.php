<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<div class="pagina-titulo-acoes">
    <h1>Inscritos: <?php echo htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="pagina-titulo-botoes">
        <a href="<?php echo url('eventos/exportarEjurr/' . (int) $evento['id']); ?>" class="btn-acao">Exportar (.csv)</a>
        <a href="<?php echo url('eventos/index'); ?>" class="btn-voltar">Voltar</a>
    </div>
</div>

<?php $modoAssistido = isset($evento['modo_credenciamento']) && $evento['modo_credenciamento'] === 'assistido'; ?>

<?php if (empty($inscricoes)): ?>
    <p>Nenhuma inscrição ainda.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr>
            <th>Nome</th><th>E-mail</th><th>Documento</th>
            <?php foreach ($campos as $campo): ?>
                <th><?php echo htmlspecialchars($campo['rotulo'], ENT_QUOTES, 'UTF-8'); ?></th>
            <?php endforeach; ?>
            <?php if ($modoAssistido): ?><th>Credenciamento</th><?php endif; ?>
        </tr>
        <?php foreach ($inscricoes as $inscricao): ?>
        <?php $respostas = $inscricao['respostas_json'] !== null ? json_decode($inscricao['respostas_json'], true) : []; ?>
        <?php
        // Fase 49B: documento/tipo de documento vêm de usuarios_perfil
        // (fonte única da pessoa), não mais de uma coluna própria de
        // evento_inscricoes - trazidos por JOIN em
        // EventoInscricaoRepository::listarPorEvento().
        $documentoExibicaoLinha = $inscricao['perfil_tipo_documento'] === 'CPF'
            ? \App\Validation\CpfValidador::formatar((string) $inscricao['perfil_documento'])
            : (string) $inscricao['perfil_documento'];
        ?>
        <tr>
            <td><?php echo htmlspecialchars($inscricao['usuario_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($inscricao['usuario_email'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($documentoExibicaoLinha, ENT_QUOTES, 'UTF-8'); ?></td>
            <?php foreach ($campos as $campo): ?>
                <?php
                $valorAtual = isset($respostas[$campo['id']]) ? (string) $respostas[$campo['id']] : '';
                $config = $campo['config_json'] !== null ? json_decode($campo['config_json'], true) : null;
                $opcoes = $config !== null && isset($config['opcoes']) ? $config['opcoes'] : [];
                ?>
                <td>
                    <?php if (\App\Core\Auth::possuiPerfil('administrador') || \App\Core\Auth::possuiPerfil('suporte')): ?>
                    <form method="post" action="<?php echo url('eventos/atualizarResposta'); ?>" style="display:flex;gap:4px;align-items:center;"><?= campoCsrf() ?>
                        <input type="hidden" name="inscricao_id" value="<?php echo (int) $inscricao['id']; ?>">
                        <input type="hidden" name="campo_id" value="<?php echo (int) $campo['id']; ?>">
                        <input type="hidden" name="evento_id" value="<?php echo (int) $evento['id']; ?>">
                        <?php if ($campo['tipo'] === 'lista_opcoes'): ?>
                            <select name="valor">
                                <option value="">Selecione</option>
                                <?php foreach ($opcoes as $opcao): ?>
                                    <option value="<?php echo htmlspecialchars($opcao, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $valorAtual === $opcao ? 'selected' : ''; ?>><?php echo htmlspecialchars($opcao, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="text" name="valor" value="<?php echo htmlspecialchars($valorAtual, ENT_QUOTES, 'UTF-8'); ?>" size="16">
                        <?php endif; ?>
                        <button type="submit" class="btn-icone" title="Salvar">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </button>
                    </form>
                    <?php else: ?>
                        <?php echo htmlspecialchars($valorAtual, ENT_QUOTES, 'UTF-8'); ?>
                    <?php endif; ?>
                </td>
            <?php endforeach; ?>
            <?php if ($modoAssistido): ?>
            <td>
                <?php if ($inscricao['homologado_em'] !== null): ?>
                    <span style="color:green;">Homologado em <?php echo htmlspecialchars(formatarDataHora($inscricao['homologado_em']), ENT_QUOTES, 'UTF-8'); ?></span>
                <?php else: ?>
                    <form method="post" action="<?php echo url('eventos/homologar'); ?>"><?= campoCsrf() ?>
                        <input type="hidden" name="inscricao_id" value="<?php echo (int) $inscricao['id']; ?>">
                        <input type="hidden" name="evento_id" value="<?php echo (int) $evento['id']; ?>">
                        <button type="submit" class="btn-icone" title="Homologar">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </button>
                    </form>
                <?php endif; ?>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
