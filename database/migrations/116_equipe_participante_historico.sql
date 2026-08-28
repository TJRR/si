-- Fase 36 (Parte B): historico de transicoes de homologacao por vinculo
-- (equipe_participante). O ENUM status_homologacao continua com 3 valores -
-- os 4 rotulos de estado usados na Parte C (selo) e na Parte A (regra de
-- acesso) sao derivados cruzando o status atual com este historico, nunca
-- gravados como estado novo na propria linha.
--
-- Motivo concreto: EquipeRepository::voltarParaPendente() (correcao de CPF,
-- Fase 35) zera motivo_rejeicao/homologado_por/homologado_em em silencio -
-- sem este historico e' impossivel distinguir um vinculo "pendente" que
-- nunca foi analisado de um que ja foi homologado/rejeitado e voltou a
-- pendente.
CREATE TABLE IF NOT EXISTS equipe_participante_historico (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vinculo_id INT UNSIGNED NOT NULL,
    status_anterior ENUM('pendente', 'homologado', 'rejeitado') NOT NULL,
    status_novo ENUM('pendente', 'homologado', 'rejeitado') NOT NULL,
    motivo TEXT NULL,
    usuario_id INT UNSIGNED NULL,
    origem VARCHAR(30) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ephistorico_vinculo FOREIGN KEY (vinculo_id) REFERENCES equipe_participante (id),
    CONSTRAINT fk_ephistorico_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    KEY idx_ephistorico_vinculo (vinculo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Preenchimento retroativo: so' vinculos que JA' tiveram uma homologacao ou
-- rejeicao real (status atual homologado/rejeitado) ganham 1 linha "marco",
-- partindo de 'pendente' (estado de nascimento de todo vinculo, migration
-- 039). Vinculos hoje pendentes ficam sem linha, o que os classifica
-- corretamente como pendente-nunca-homologado.
--
-- NAO cobre o caso raro de um vinculo pendente que ja tenha passado por
-- voltarParaPendente() ANTES desta migration rodar (perderia o rastro, pois
-- aquele UPDATE ja apaga homologado_em/motivo_rejeicao na propria linha) -
-- decisao aceita porque, na pratica, o unico chamador de
-- voltarParaPendente() e' o fluxo de correcao de CPF da Fase 35, que ainda
-- nao esta em producao, e o processo de deploy ja inclui confirmar que nao
-- ha' ninguem pendente antes do corte (ver DeployFase36.md).
INSERT INTO equipe_participante_historico (vinculo_id, status_anterior, status_novo, motivo, usuario_id, origem, criado_em)
SELECT id, 'pendente', status_homologacao, motivo_rejeicao, homologado_por, 'retroativo', COALESCE(homologado_em, NOW())
FROM equipe_participante
WHERE status_homologacao IN ('homologado', 'rejeitado')
  AND id NOT IN (SELECT vinculo_id FROM equipe_participante_historico);
