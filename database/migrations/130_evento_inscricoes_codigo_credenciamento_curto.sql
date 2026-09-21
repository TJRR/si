-- Fase 42 (correcao pos-teste de fumaca): codigo de credenciamento passa de
-- 32 caracteres hexadecimais para 6 caracteres (alfabeto Crockford Base32,
-- EventoInscricaoRepository::ALFABETO_CODIGO_CREDENCIAMENTO) - formato curto
-- pensado pra digitacao manual de fallback, caso a leitura do QR falhe.
-- Os valores ja gravados no formato antigo nao cabem em CHAR(6) e sao
-- zerados aqui; o script ja existente (gerar_codigos_credenciamento_pendentes.php)
-- os repovoa no formato novo, sem precisar de nenhum script adicional.
UPDATE evento_inscricoes SET codigo_credenciamento = NULL;

ALTER TABLE evento_inscricoes
    MODIFY COLUMN codigo_credenciamento CHAR(6) NULL;
