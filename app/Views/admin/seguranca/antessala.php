<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<?php
    // Fase 35: esta tela nao mostra nada sensivel, nao registra auditoria e
    // nao avisa ninguem - de proposito. Ela e' o consentimento ANTES de um
    // disparo irreversivel: abrir a tela seguinte notifica, no mesmo
    // instante, todos os outros administradores globais. Sem esta parada,
    // quem clicasse na aba por curiosidade ja teria alertado os colegas.
?>
<div class="seguranca-antessala">
    <div class="seguranca-antessala-cartao">
        <div class="seguranca-antessala-icone" aria-hidden="true">🔐</div>

        <h1>Configuração de segurança</h1>

        <p class="seguranca-antessala-aviso">
            O <strong>simples</strong> acesso a esta aba notifica todos os outros administradores
            e fica registrado na trilha de auditoria com seu nome, IP e horário.
            Deseja prosseguir?
        </p>

        <p class="seguranca-antessala-detalhe">
            Aqui ficam as credenciais que ligam o sistema ao Google e ao envio de e-mail.
            Nenhum valor de credencial é exibido em tela: só a identificação de qual está
            instalada e quem a alterou pela última vez.
        </p>

        <form method="post" action="<?php echo url('seguranca/abrir'); ?>"><?= campoCsrf() ?>
            <button type="submit" class="btn-acao">Abrir configuração de segurança</button>
        </form>

        <p class="seguranca-antessala-detalhe">
            <a href="<?php echo url('configuracoes/index'); ?>">Voltar para Configurações</a>
        </p>
    </div>
</div>
