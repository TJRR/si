-- Complemento da migration 140: aquela guardava so' a sigla (instituicao,
-- unidade_responsavel). Alguns textos do sistema (rodape, e-mails formais)
-- usam o nome por extenso, nao a sigla - precisam de campo proprio em vez
-- de reaproveitar a sigla como se fosse o nome completo. Valores padrao
-- preservam o texto que ja existia fixo no codigo antes desta migration.
ALTER TABLE configuracoes_sistema
    ADD COLUMN instituicao_nome_completo VARCHAR(255) NOT NULL DEFAULT 'Tribunal de Justiça do Estado de Roraima',
    ADD COLUMN unidade_responsavel_nome_completo VARCHAR(255) NOT NULL DEFAULT 'Núcleo de Projetos e Inovação';
