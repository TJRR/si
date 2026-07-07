<?php if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
} ?>
<header>
    <h1>Sistema de Gestão da Semana de Inovação e do Prêmio de Inovação do TJRR</h1>
</header>

<main>
    <p>
        Este é o Sistema de Gestão da Semana de Inovação e do Prêmio de Inovação do TJRR,
        utilizado pelo Núcleo de Projetos e Inovação (NPI) do Tribunal de Justiça de Roraima
        para inscrição, avaliação e acompanhamento de projetos submetidos à Semana de Inovação
        e ao Prêmio de Inovação do Tribunal de Justiça de Roraima.
    </p>
    <p>
        Pelo sistema, equipes e participantes se inscrevem, submetem suas ideias e projetos
        vinculados aos temas/desafios de cada edição, e acompanham o andamento das etapas do
        certame. A avaliação das submissões é realizada por avaliadores designados pelo NPI,
        conforme os critérios de cada edital.
    </p>

    <nav>
        <p><a href="<?php echo url('auth/login'); ?>">Entrar</a></p>
        <p><a href="<?php echo url('cadastro/index'); ?>">Criar cadastro</a></p>
    </nav>

    <footer>
        <p>
            <a href="<?php echo config('base_path'); ?>/politica.php">Política de Privacidade</a>
            &nbsp;|&nbsp;
            <a href="<?php echo config('base_path'); ?>/termos.php">Termos de Serviço</a>
        </p>
        <p>Contato: <a href="mailto:npi@tjrr.jus.br">npi@tjrr.jus.br</a></p>
    </footer>
</main>
