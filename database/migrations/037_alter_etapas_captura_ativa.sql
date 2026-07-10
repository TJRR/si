-- Liga/desliga a captura de inscricoes (formulario publico, sem login) na
-- etapa "Cadastro de Equipe" (ordem=1) de cada trilha.
ALTER TABLE etapas
    ADD COLUMN captura_ativa TINYINT(1) NOT NULL DEFAULT 0;
