-- Fase 30 (validacao automatica no ITI): 3 colunas temporarias em
-- requerimentos pra abrir uma janela curta e de uso unico em que o PDF
-- assinado fica publicamente buscavel so' pelo validador oficial do governo
-- (validar.iti.gov.br) chamar via URL, nunca por navegacao direta - ver
-- App\Services\ItiValidadorService e App\Controllers\ValidacaoPublicaController.
-- O token bruto nunca e' gravado, so' o hash (sha256); iti_token_usado_em
-- marca reivindicacao de uso unico (RequerimentoRepository::
-- reivindicarTokenValidacaoIti(), UPDATE atomico) e iti_token_expira_em
-- limita a janela a poucos minutos mesmo se nunca for reivindicado.
ALTER TABLE requerimentos
    ADD COLUMN iti_token_hash CHAR(64) NULL AFTER pdf_assinado_nome_original,
    ADD COLUMN iti_token_expira_em DATETIME NULL AFTER iti_token_hash,
    ADD COLUMN iti_token_usado_em DATETIME NULL AFTER iti_token_expira_em;
