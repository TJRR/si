-- Fase 25: modo de manutencao - flag unico que, quando ligado, bloqueia o
-- sistema inteiro (exceto o administrador) via App\Core\Router::despachar().
-- Sem colunas de "quem/quando desativou" - isso ja fica registrado via
-- Auditoria::registrar() a cada toggle, igual a todo outro ajuste
-- administrativo do sistema (ver ConfiguracaoSistemaRepository).
ALTER TABLE configuracoes_sistema
    ADD COLUMN sistema_desativado TINYINT(1) NOT NULL DEFAULT 0;
