<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 41: menu do aplicativo do Evento - dropdown ancorado no proprio
 * botao hamburguer (.app-bar-botao em _app_bar.php, dentro de .app-bar,
 * que e' position:relative), abre logo abaixo dele. Sem cabecalho interno
 * proprio ("Menu"/botao fechar) - o mesmo botao que abre tambem fecha
 * (assets/js/painel-lateral.js trata isso como alternancia), e clicar fora
 * (no backdrop transparente) tambem fecha. Efeito deliberadamente diferente
 * do painel lateral (desliza da direita) usado pelo cronograma/ajuda da
 * home publica. $eventoId (opcional, definido pela view antes de incluir
 * este parcial) e' o evento sendo visto - some o item "Minha inscrição"
 * quando ausente (ex.: tela "Meus eventos", que lista mais de um). O botao
 * de ajuda contextual mora na app-bar (icone flutuante, ver _app_bar.php),
 * nao aqui - este menu e' so' navegacao. Reaproveitado tambem pela tela de
 * entrada de quem ainda nao tem conta, acessando por celular
 * (publico/evento_inscricao.php, quando ehDispositivoMovel()) - por isso o
 * ramo para visitante anonimo abaixo.
 */
?>
<aside id="painel-menu-app" class="app-menu-lateral" aria-hidden="true" aria-label="Menu">
    <nav class="app-menu-corpo">
        <?php if (\App\Core\Auth::autenticado()): ?>
            <a href="<?php echo url('eventoApp/index' . (isset($eventoId) ? '/' . (int) $eventoId : '')); ?>">Painel</a>
            <?php if (isset($eventoId)): ?>
                <a href="<?php echo url('eventoApp/inscricao/' . (int) $eventoId); ?>">Minha inscrição</a>
            <?php endif; ?>
            <a href="<?php echo url('auth/logout'); ?>" class="app-menu-sair">Sair</a>
        <?php else: ?>
            <a href="<?php echo url('home/index'); ?>">Voltar ao início</a>
        <?php endif; ?>
    </nav>
</aside>
<div class="app-menu-backdrop" data-fechar-painel></div>
