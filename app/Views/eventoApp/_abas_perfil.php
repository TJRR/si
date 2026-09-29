<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
/**
 * Fase 55: navegacao entre as tres abas de "Meu Perfil" dentro do aplicativo
 * do Evento. Nao usa NavegacaoService de proposito: aquele servico monta a
 * arvore lateral e as abas do painel administrativo, que o aplicativo nao
 * tem. Aqui sao tres caminhos simples, com o atual marcado.
 *
 * $evento e $abaAtiva ('dados', 'aparencia' ou 'senha') sao definidos pela
 * view antes de incluir este parcial. A aba de senha some para quem entra
 * so' pela conta Google, que nao tem senha a trocar.
 */
$eventoIdAbas = (int) $evento['id'];
$mostrarAbaSenha = !empty($temSenha);
?>
<p class="perfil-abas-app">
    <a href="<?php echo url('eventoAppPerfil/index/' . $eventoIdAbas); ?>"<?php echo $abaAtiva === 'dados' ? ' class="ativo"' : ''; ?>>Meus dados</a>
    ·
    <a href="<?php echo url('eventoAppPerfil/aparencia/' . $eventoIdAbas); ?>"<?php echo $abaAtiva === 'aparencia' ? ' class="ativo"' : ''; ?>>Aparência</a>
    <?php if ($mostrarAbaSenha): ?>
        ·
        <a href="<?php echo url('eventoAppPerfil/senha/' . $eventoIdAbas); ?>"<?php echo $abaAtiva === 'senha' ? ' class="ativo"' : ''; ?>>Alterar senha</a>
    <?php endif; ?>
</p>
