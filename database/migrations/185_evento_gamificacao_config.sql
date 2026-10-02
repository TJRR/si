-- Fase 58 (30/09/2026): configuracao da Gamificacao do evento, uma linha por
-- evento, no molde de evento_bonus_config (184). Criada so' quando o
-- Administrador salva a tela; evento sem linha e' evento com o modulo
-- desligado, sem classificacao visivel e sem extra de pontualidade.
--
-- Uma instrucao por arquivo nesta fase (185 a 197): database/migrate.php
-- executa o arquivo inteiro num unico exec(), e com varias instrucoes um
-- erro depois da primeira pode nao virar excecao, deixando o arquivo
-- registrado pela metade. Com uma so', o erro sempre aparece.
--
-- ativo: mostra "Minha pontuacao", "Regras do jogo" e o cartao do painel no
-- aplicativo. Os creditos sao calculados independentemente desta chave,
-- como na Fase 57.
--
-- classificacao_visivel / classificacao_quantidade / classificacao_mostrar_nomes:
-- o inscrito ve a propria posicao e os N primeiros; os nomes aparecem so'
-- com a terceira marca ligada (decisao do dono).
--
-- minutos_pontualidade: nulo significa que o extra de pontualidade nao se
-- aplica. O extra vale para a leitura feita ate' N minutos antes do inicio.
--
-- encerramento_em: instante a partir do qual nada mais pontua e a
-- classificacao fica congelada. Pode ser alterado enquanto esta' no futuro;
-- depois que chega, e' definitivo (GamificacaoAdminController recusa).
CREATE TABLE IF NOT EXISTS evento_gamificacao_config (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 0,
    classificacao_visivel TINYINT(1) NOT NULL DEFAULT 0,
    classificacao_quantidade SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    classificacao_mostrar_nomes TINYINT(1) NOT NULL DEFAULT 0,
    minutos_pontualidade SMALLINT UNSIGNED NULL,
    texto_regras_html MEDIUMTEXT NULL,
    encerramento_em DATETIME NULL,
    encerramento_definido_por INT UNSIGNED NULL,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_evento_gamificacao_config_evento (evento_id),
    CONSTRAINT fk_evento_gamificacao_config_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    CONSTRAINT fk_evento_gamificacao_config_definido_por FOREIGN KEY (encerramento_definido_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
