-- Fase 17 (Bug 2): equipes passam a apontar para o Desafio escolhido (nao
-- mais para o Tema). "tema_desafio_id" nunca era escrito por nenhum codigo
-- ate esta fase (confirmado: sempre NULL em npi_si_dev) - seguro remover sem
-- migrar dado nenhum.
ALTER TABLE equipes
    DROP FOREIGN KEY fk_equipes_tema_desafio,
    DROP COLUMN tema_desafio_id,
    ADD COLUMN desafio_id INT UNSIGNED NULL AFTER trilha_id,
    ADD CONSTRAINT fk_equipes_desafio FOREIGN KEY (desafio_id) REFERENCES desafios (id);
