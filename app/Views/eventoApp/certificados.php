<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 59: os certificados a que a pessoa tem direito, com o botao de emitir
 * ou de baixar cada um.
 *
 * A lista vem apurada pelo controlador: esta view nunca decide quem tem
 * direito a que. Cada item traz a propria chave, e e' a mesma chave que a
 * emissao confere - entao a tela nunca oferece o que o servidor recusaria.
 *
 * Quem nao tem nenhum item ve' por escrito o que falta, com os numeros dele
 * ao lado das exigencias, em vez de uma tela vazia.
 */
$eventoId = (int) $evento['id'];
?>

<div class="site-page">
    <?php
    $tituloTopo = $evento['nome'];
    $urlVoltar = url('eventoApp/index/' . $eventoId);
    require __DIR__ . '/_app_bar.php';
    ?>

    <div class="site-form-page">
        <?php require __DIR__ . '/_ajuda_card.php'; ?>
        <h2>Meus certificados</h2>

        <?php if ($dossie['itens'] === []): ?>
            <div class="admin-card">
                <p>Você ainda não tem certificado a retirar neste evento.</p>
                <?php if ($exigencias !== []): ?>
                    <p>Para o certificado de participação no evento é preciso:</p>
                    <ul>
                        <?php if (isset($exigencias['min_atividades'])): ?>
                            <li>
                                <?php echo (int) $exigencias['min_atividades']; ?> atividade(s) diferente(s) com
                                presença confirmada. Você tem <?php echo (int) $dossie['atividades']; ?>.
                            </li>
                        <?php endif; ?>
                        <?php if (isset($exigencias['min_dias'])): ?>
                            <li>
                                <?php echo (int) $exigencias['min_dias']; ?> dia(s) diferente(s) de evento.
                                Você tem <?php echo (int) $dossie['dias']; ?>.
                            </li>
                        <?php endif; ?>
                        <?php if (isset($exigencias['min_horas'])): ?>
                            <li>
                                <?php echo (int) $exigencias['min_horas']; ?> hora(s) de participação.
                                Você tem <?php echo htmlspecialchars($cargaHoraria, ENT_QUOTES, 'UTF-8'); ?>.
                            </li>
                        <?php endif; ?>
                        <?php if (isset($exigencias['exige_credenciamento'])): ?>
                            <li>
                                Credenciamento no local.
                                <?php echo !empty($dossie['credenciado']) ? 'Você já se credenciou.' : 'Você ainda não se credenciou.'; ?>
                            </li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="admin-card">
                <p>
                    <strong>Sua participação apurada:</strong>
                    <?php echo (int) $dossie['atividades']; ?>
                    <?php echo (int) $dossie['atividades'] === 1 ? 'atividade' : 'atividades'; ?>,
                    <?php echo (int) $dossie['dias']; ?>
                    <?php echo (int) $dossie['dias'] === 1 ? 'dia' : 'dias'; ?>,
                    <?php echo htmlspecialchars($cargaHoraria, ENT_QUOTES, 'UTF-8'); ?> de carga horária.
                </p>
                <p><small>
                    A carga horária soma a união dos horários: duas atividades no mesmo horário contam uma
                    vez. O certificado fica guardado como foi emitido, então pode ser baixado quantas vezes
                    você precisar.
                </small></p>
            </div>

            <?php foreach ($dossie['itens'] as $item): ?>
                <?php $emitido = isset($emitidos[$item['chave']]) ? $emitidos[$item['chave']] : null; ?>
                <div class="admin-card">
                    <p>
                        <strong><?php echo htmlspecialchars($item['rotulo'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <?php if (!empty($item['detalhe'])): ?>
                            <br><small><?php echo htmlspecialchars($item['detalhe'], ENT_QUOTES, 'UTF-8'); ?></small>
                        <?php endif; ?>
                        <?php if ($item['carga_horaria_minutos'] !== null): ?>
                            <br><small>
                                Carga horária:
                                <?php echo htmlspecialchars(\App\Services\CertificadoElegibilidadeService::formatarCargaHoraria($item['carga_horaria_minutos']), ENT_QUOTES, 'UTF-8'); ?>
                            </small>
                        <?php endif; ?>
                    </p>
                    <?php if ($emitido !== null && $emitido['cancelado_em'] !== null): ?>
                        <p class="status-pill vermelho">
                            Este certificado foi cancelado pela organização em
                            <?php echo formatarData($emitido['cancelado_em']); ?>.
                            <?php if (!empty($emitido['motivo_cancelamento'])): ?>
                                Motivo: <?php echo htmlspecialchars($emitido['motivo_cancelamento'], ENT_QUOTES, 'UTF-8'); ?>
                            <?php endif; ?>
                        </p>
                    <?php elseif ($emitido !== null): ?>
                        <p>
                            <a href="<?php echo url('eventoApp/certificadoArquivo/' . $eventoId . '/' . (int) $emitido['id']); ?>" class="btn" target="_blank" rel="noopener">Baixar o certificado</a>
                            <br><small>
                                Emitido em <?php echo formatarData($emitido['emitido_em']); ?>.
                                Código de conferência <?php echo htmlspecialchars($emitido['codigo_verificacao'], ENT_QUOTES, 'UTF-8'); ?>.
                            </small>
                        </p>
                    <?php else: ?>
                        <form method="post" action="<?php echo url('eventoApp/emitirCertificado/' . $eventoId); ?>"><?= campoCsrf() ?>
                            <input type="hidden" name="chave" value="<?php echo htmlspecialchars($item['chave'], ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" class="btn">Emitir o certificado</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
