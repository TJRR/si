<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
// Fase 51, refeita na reabertura: secao em duas colunas, como na identidade
// visual aprovada para a submissao de trabalhos. A esquerda, etiqueta,
// titulo, texto, ate tres botoes e o contato (e-mail e WhatsApp do contato
// global do site, o mesmo do rodape); a direita, o quadro branco com a
// linha do tempo. O destino dos botoes com documento (1 e 3) ja chega resolvido pelo
// controller (documento do evento ou endereco digitado).
$cabecalhoProprio = true;
$urlBotao1 = isset($dadosSecao['botao1_url']) ? $dadosSecao['botao1_url'] : '';
$urlBotao2 = linkPublico(isset($dadosSecao['botao2_link']) ? $dadosSecao['botao2_link'] : '');
$urlBotao3 = isset($dadosSecao['botao3_url']) ? $dadosSecao['botao3_url'] : '';
$mostrarBotao1 = !empty($dadosSecao['botao1_titulo']) && $urlBotao1 !== '';
$mostrarBotao2 = !empty($dadosSecao['botao2_titulo']) && $urlBotao2 !== '';
$mostrarBotao3 = !empty($dadosSecao['botao3_titulo']) && $urlBotao3 !== '';
$mostrarContato = !empty($dadosSecao['mostrar_contato']) && $contato !== null && (!empty($contato['email']) || !empty($contato['whatsapp']));
$botao1AbreDocumento = !empty($dadosSecao['botao1_documento_id']);
$botao3AbreDocumento = !empty($dadosSecao['botao3_documento_id']);
?>
<?php include __DIR__ . '/_secao_abre.php'; ?>
        <div class="evento-duas-colunas evento-submissao">
            <div class="evento-submissao-texto">
                <?php include __DIR__ . '/_secao_cabecalho.php'; ?>

                <?php if ($mostrarBotao1 || $mostrarBotao2 || $mostrarBotao3): ?>
                    <div class="evento-botoes">
                        <?php if ($mostrarBotao1): ?>
                            <a href="<?php echo htmlspecialchars($urlBotao1, ENT_QUOTES, 'UTF-8'); ?>" class="evento-botao evento-botao-escuro"<?php echo $botao1AbreDocumento ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo htmlspecialchars($dadosSecao['botao1_titulo'], ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php endif; ?>
                        <?php if ($mostrarBotao2): ?>
                            <a href="<?php echo htmlspecialchars($urlBotao2, ENT_QUOTES, 'UTF-8'); ?>" class="evento-botao evento-botao-contorno"><?php echo htmlspecialchars($dadosSecao['botao2_titulo'], ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php endif; ?>
                        <?php if ($mostrarBotao3): ?>
                            <a href="<?php echo htmlspecialchars($urlBotao3, ENT_QUOTES, 'UTF-8'); ?>" class="evento-botao evento-botao-contorno"<?php echo $botao3AbreDocumento ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo htmlspecialchars($dadosSecao['botao3_titulo'], ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($mostrarContato): ?>
                    <ul class="evento-contato-icones" aria-label="Contato para dúvidas">
                        <?php if (!empty($contato['email'])): ?>
                            <li>
                                <a href="mailto:<?php echo htmlspecialchars($contato['email'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 6 12 13 2 6"></path><rect x="2" y="4" width="20" height="16" rx="2"></rect></svg>
                                    <span><?php echo htmlspecialchars($contato['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php $whatsappSecao = !empty($contato['whatsapp']) ? linkWhatsApp($contato['whatsapp']) : null; ?>
                        <?php if ($whatsappSecao !== null): ?>
                            <li>
                                <a href="<?php echo htmlspecialchars($whatsappSecao, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                                    <svg class="evento-icone-whatsapp" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="#25D366" d="M12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0 0 20.463 3.488 11.815 11.815 0 0 0 12.05 0Z"/><path fill="#ffffff" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347Z"/></svg>
                                    <span><?php echo htmlspecialchars($contato['whatsapp'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <?php if (!empty($itensSecao)): ?>
                <div class="evento-quadro-branco">
                    <?php if (!empty($dadosSecao['titulo_quadro'])): ?>
                        <h3 class="evento-quadro-titulo"><?php echo htmlspecialchars($dadosSecao['titulo_quadro'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <?php endif; ?>
                    <ol class="evento-linha-tempo">
                        <?php foreach ($itensSecao as $item): ?>
                            <li>
                                <span class="evento-linha-tempo-marcador" style="<?php echo estiloDeCores($item['cor']); ?>" aria-hidden="true"></span>
                                <div>
                                    <strong><?php echo htmlspecialchars($item['periodo_texto'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span><?php echo htmlspecialchars($item['descricao'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            <?php endif; ?>
        </div>
<?php include __DIR__ . '/_secao_fecha.php'; ?>
