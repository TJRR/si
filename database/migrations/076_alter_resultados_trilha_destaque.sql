-- Fase 18 (4.7): campos opcionais de destaque do case vencedor, editaveis
-- pelo admin na tela de resultados publicados - complementam (nao
-- substituem) os dados ja existentes da submissao, que nao tem campo de
-- imagem nem um resumo pensado para leitura publica.
ALTER TABLE resultados_trilha
    ADD COLUMN resumo_destaque TEXT NULL,
    ADD COLUMN imagem_destaque_path VARCHAR(255) NULL,
    ADD COLUMN imagem_destaque_alt VARCHAR(255) NULL;
