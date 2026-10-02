<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 59: conferencia publica de certificado. Tela do fluxo do Evento, com a
 * aparencia das demais telas de visitante.
 *
 * Mostra nome, condicao, evento, periodo, carga horaria, codigo e data de
 * emissao. NAO mostra o documento de identificacao e NAO entrega o arquivo:
 * quem tem o codigo ja' tem o certificado em maos, e a pagina existe para
 * conferir, nao para distribuir dado pessoal.
 */
$logoSrc = logoAtual(true);
$tipos = [
    \App\Services\CertificadoElegibilidadeService::TIPO_EVENTO => 'Participação no evento',
    \App\Services\CertificadoElegibilidadeService::TIPO_ATIVIDADE => 'Atividade do evento',
    \App\Services\CertificadoElegibilidadeService::TIPO_APRESENTACAO => 'Apresentação de trabalho',
];
?>
<div class="guest-card">
    <img src="<?php echo htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="guest-logo">

    <h1 class="guest-titulo">Conferência de certificado</h1>
    <p class="guest-subtitulo">
        Digite o código impresso no pé do certificado para conferir se ele foi emitido por este sistema.
    </p>

    <form method="get" action="<?php echo config('base_path'); ?>/index.php">
        <input type="hidden" name="r" value="certificadoPublico/index">
        <label>
            Código de conferência
            <input type="text" name="codigo" required maxlength="10" size="14" value="<?php echo htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <button type="submit" class="btn btn-bordered">Conferir</button>
    </form>

    <?php if (!empty($erro)): ?>
        <p class="status-pill laranja"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <?php if (!empty($certificado)): ?>
        <?php $cancelado = $certificado['cancelado_em'] !== null; ?>
        <div class="admin-card">
            <?php if ($cancelado): ?>
                <p class="status-pill vermelho">
                    Este certificado foi cancelado pela organização em
                    <?php echo formatarData($certificado['cancelado_em']); ?> e não vale mais como comprovação.
                </p>
            <?php else: ?>
                <p class="status-pill verde">Certificado válido, emitido por este sistema.</p>
            <?php endif; ?>

            <p>
                <strong><?php echo htmlspecialchars($certificado['nome'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                <?php echo htmlspecialchars(isset($tipos[$certificado['tipo']]) ? $tipos[$certificado['tipo']] : $certificado['tipo'], ENT_QUOTES, 'UTF-8'); ?>
                <?php if (!empty($certificado['atividade_nome'])): ?>
                    : <?php echo htmlspecialchars($certificado['atividade_nome'], ENT_QUOTES, 'UTF-8'); ?>
                <?php elseif (!empty($certificado['trabalho_titulo'])): ?>
                    : <?php echo htmlspecialchars($certificado['trabalho_titulo'], ENT_QUOTES, 'UTF-8'); ?>
                <?php endif; ?>
            </p>
            <p>
                Evento: <?php echo htmlspecialchars($certificado['evento_nome'], ENT_QUOTES, 'UTF-8'); ?><br>
                <?php if (!empty($certificado['periodo_inicio'])): ?>
                    Período: <?php echo formatarData($certificado['periodo_inicio']); ?>
                    a <?php echo formatarData($certificado['periodo_fim']); ?><br>
                <?php endif; ?>
                <?php if (!empty($certificado['condicoes'])): ?>
                    Condição: <?php echo htmlspecialchars($certificado['condicoes'], ENT_QUOTES, 'UTF-8'); ?><br>
                <?php endif; ?>
                <?php if ($cargaHoraria !== null): ?>
                    Carga horária: <?php echo htmlspecialchars($cargaHoraria, ENT_QUOTES, 'UTF-8'); ?><br>
                <?php endif; ?>
                Emitido em: <?php echo formatarData($certificado['emitido_em']); ?><br>
                Código: <?php echo htmlspecialchars($certificado['codigo_verificacao'], ENT_QUOTES, 'UTF-8'); ?>
            </p>
            <p><small>
                Esta página confere o que foi emitido. Ela não entrega o arquivo nem mostra o documento de
                identificação de quem recebeu.
            </small></p>
        </div>
    <?php endif; ?>
</div>
