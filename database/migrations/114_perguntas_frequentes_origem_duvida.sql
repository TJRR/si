-- Fase 35 (Parte B): rastro da duvida que deu origem a uma pergunta do banco
-- global de FAQ. Uma duvida real, ja respondida, costuma valer pra todo
-- mundo - o Administrador/Suporte aproveita o texto como pergunta/resposta
-- generica em vez de deixar a resposta morrer no canal privado da equipe.
--
-- NULL de proposito, e nao NOT NULL com backfill: a imensa maioria das
-- perguntas do banco NAO nasce de duvida nenhuma (foram escritas direto em
-- FAQ > Nova pergunta, desde a Fase 18), e as que ja existem em producao
-- nao tem origem nenhuma pra migrar.
--
-- E' rastro INTERNO, de auditoria/administracao - serve pra (1) mostrar na
-- tela da duvida que ela ja virou FAQ e qual item, (2) avisar quem for
-- promover a mesma duvida de novo, e (3) dar contexto a quem editar o FAQ
-- depois. NUNCA e' exibido na home nem em qualquer pagina publica: o item
-- de FAQ e' generico e nao carrega vinculo visivel com a equipe que
-- perguntou (ver app/Views/home/_faq.php, que le so' pergunta/resposta/
-- categoria).
--
-- Sem ON DELETE CASCADE: apagar uma duvida nunca deve apagar em silencio uma
-- pergunta que ja esta publicada numa edicao. A FK segura a exclusao, que e'
-- o comportamento desejado.
ALTER TABLE perguntas_frequentes
    ADD COLUMN duvida_id INT UNSIGNED NULL AFTER categoria,
    ADD CONSTRAINT fk_perguntas_frequentes_duvida FOREIGN KEY (duvida_id) REFERENCES duvidas (id),
    ADD KEY idx_perguntas_frequentes_duvida (duvida_id);
