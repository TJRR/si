-- Fase 60 (pendencia 29): prazo, em dias depois do fim do evento, para a
-- rotina database/expurgar_imagens_divulgacao.php apagar as imagens de
-- comprovacao. Em branco, sem expurgo automatico.
ALTER TABLE evento_divulgacao_config
    ADD COLUMN dias_retencao_imagens SMALLINT UNSIGNED NULL AFTER data_fim;
