-- Fase 41: identidade do aplicativo web instalavel (PWA) do Evento -
-- editavel pelo Administrador em Configuracoes Gerais, nunca fixa no codigo
-- (regra permanente do projeto: toda entidade/configuracao de negocio nova
-- precisa de tela administrativa). icone_app_atualizado_em nao guarda path -
-- os 3 arquivos gerados (icon-192.png/icon-512.png/icon-512-maskable.png,
-- ver ImagemService::salvarIconeApp()) tem nome fixo, sobrescritos a cada
-- novo upload; a coluna serve so' de cache-buster (?v=<timestamp>) nas
-- referencias do manifesto/HTML.
ALTER TABLE configuracoes_sistema
    ADD COLUMN nome_app VARCHAR(60) NULL,
    ADD COLUMN nome_app_curto VARCHAR(20) NULL,
    ADD COLUMN icone_app_atualizado_em DATETIME NULL;
