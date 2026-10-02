-- Fase 58: campos exigidos pelo bonus do tipo perfil_campos (por exemplo
-- "foto,cargo,orgao_origem"), gravados sempre na mesma ordem para que a
-- trava de duplicata compare listas iguais. Nulo nos demais tipos.
ALTER TABLE evento_bonus
    ADD COLUMN campos_perfil VARCHAR(200) NULL AFTER tipo_atividade_id;
