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

/**
 * Fase 57 (bloco E): "Minha inscricao" e "Meu Perfil" so' aparecem para
 * quem tem inscricao no evento. Sem isso, o facilitador (que entra no
 * aplicativo sem nunca ter se inscrito) via dois itens de menu que apenas o
 * devolviam para a tela de inscricao: dois becos sem saida.
 *
 * A view diz o que sabe: $temInscricao quando conferiu, $inscricao quando
 * ja' tem a linha em maos. Sem nenhum dos dois, o menu confere sozinho, uma
 * vez, e so' nas telas que passam o evento.
 */
$menuTemInscricao = true;

if (isset($temInscricao)) {
    $menuTemInscricao = (bool) $temInscricao;
} elseif (array_key_exists('inscricao', get_defined_vars())) {
    $menuTemInscricao = !empty($inscricao);
} elseif (isset($eventoId) && \App\Core\Auth::autenticado()) {
    $menuTemInscricao = (new \App\Repositories\EventoInscricaoRepository())
        ->buscarPorEventoEUsuario($eventoId, \App\Core\Auth::usuarioId()) !== null;
}
?>
<aside id="painel-menu-app" class="app-menu-lateral" aria-hidden="true" aria-label="Menu">
    <nav class="app-menu-corpo">
        <?php if (\App\Core\Auth::autenticado()): ?>
            <a href="<?php echo url('eventoApp/index' . (isset($eventoId) ? '/' . (int) $eventoId : '')); ?>">Painel</a>
            <?php if (isset($eventoId) && $menuTemInscricao): ?>
                <a href="<?php echo url('eventoApp/inscricao/' . (int) $eventoId); ?>">Minha inscrição</a>
                <?php /* Fase 55: "Meu Perfil" passa a existir dentro do
                aplicativo (dados, aparência e senha) - antes só havia a tela
                do painel administrativo, sem nenhum caminho até ela para
                quem é apenas inscrito em evento. */ ?>
                <a href="<?php echo url('eventoAppPerfil/index/' . (int) $eventoId); ?>">Meu Perfil</a>
            <?php endif; ?>
            <a href="<?php echo url('auth/logout'); ?>" class="app-menu-sair">Sair</a>
        <?php else: ?>
            <?php /* Com o evento conhecido (formulario de submissao de
            trabalhos aberto por visitante), o inicio e' a pagina do proprio
            evento, nunca a pagina inicial do Concurso. */ ?>
            <a href="<?php echo isset($eventoId) ? htmlspecialchars(urlPaginaEvento($eventoId), ENT_QUOTES, 'UTF-8') : url('home/index'); ?>">Voltar ao início</a>
        <?php endif; ?>
    </nav>
</aside>
<div class="app-menu-backdrop" data-fechar-painel></div>
