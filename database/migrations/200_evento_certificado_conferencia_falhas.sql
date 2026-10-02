-- Fase 59: limite de tentativas da pagina publica de conferencia de
-- certificado, no molde de tentativas_login_falhas (110).
--
-- Uma instrucao por arquivo, mesma regra da 185.
--
-- A pagina e' aberta a quem nao tem conta e recebe um codigo digitado, entao
-- sem limite ela e' um caminho para descobrir certificado por tentativa
-- automatica. A chave do limite e' so' o endereco de origem: nao existe
-- identificacao de quem consulta, e usar o codigo tentado como chave puniria
-- o certificado em vez de quem tenta.
--
-- Sem chave estrangeira nenhuma, de proposito: a linha nasce ANTES de se
-- saber se o codigo corresponde a algum certificado, e e' justamente o caso
-- em que nao corresponde que precisa ser contado.
--
-- Nao ha' rotina de expurgo: a contagem olha apenas os ultimos minutos
-- (CertificadoConferenciaFalhaRepository), como a de entrada ja' faz desde a
-- 110. O crescimento da tabela acompanha o das tentativas falhas, que num
-- evento com tres dias de duracao e' pequeno.
CREATE TABLE IF NOT EXISTS evento_certificado_conferencia_falhas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_origem VARCHAR(45) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_certificado_conferencia_falhas_ip_criado (ip_origem, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
