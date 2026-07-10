-- Remove a ponte historica de importacao via CSV (RevisaoImportacaoController
-- e o script bin/importar_equipes_google_forms.php ja foram removidos do
-- codigo) - os 128 vinculos equipe_participante ja foram marcados como
-- homologados por database/homologar_dados_historicos.php antes desta
-- migracao rodar.
DROP TABLE IF EXISTS importacao_pendencias;

ALTER TABLE equipes
    DROP COLUMN importado_em;
