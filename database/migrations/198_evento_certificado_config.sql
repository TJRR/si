-- Fase 59 (01/10/2026): configuracao dos Certificados do evento, uma linha
-- por evento, no molde de evento_gamificacao_config (185) e
-- evento_bonus_config (184). Criada so' quando o Administrador salva a tela;
-- evento sem linha e' evento sem certificado nenhum.
--
-- Uma instrucao por arquivo, mesma regra escrita na 185: database/migrate.php
-- executa o arquivo inteiro num unico exec(), e com varias instrucoes um erro
-- depois da primeira pode nao virar excecao.
--
-- ativo: liga o modulo. Desligado, nenhuma tela de participante mostra
-- caminho para certificado e a emissao e' recusada no servidor.
--
-- min_atividades / min_dias / min_horas / exige_credenciamento: a regua de
-- elegibilidade do certificado DO EVENTO, para quem e' inscrito. NULO
-- significa "nao exige" - o sistema nao assume valor que o Administrador nao
-- escreveu. Os criterios preenchidos valem TODOS JUNTOS (decisao do dono):
-- quem tem direito cumpre cada um deles. Com os quatro nulos, todo inscrito
-- e' elegivel, e a tela diz isso por escrito.
--
-- min_horas esta' em HORAS INTEIRAS, que e' o que a tela pede e o que a
-- organizacao raciocina. A apuracao trabalha em minutos (a uniao dos
-- intervalos das atividades) e compara com min_horas vezes 60, em
-- CertificadoElegibilidadeService::cumpreARegua().
--
-- Facilitador e avaliador nao passam pela regua: a designacao deles e' a
-- propria prova de participacao, e nenhum dos dois tem presenca registravel
-- (evento_checkins e' chaveada por evento_inscricao_id).
--
-- carga_horaria_avaliador_horas: numero declarado pela organizacao para a
-- funcao de avaliador de Trabalhos, igual para todos, em HORAS INTEIRAS - a
-- mesma unidade do certificado, que declara horas. A avaliacao acontece fora
-- dos dias do evento e nao tem horario nenhum no sistema, entao nao ha' o que
-- calcular. Nulo deixa o certificado do avaliador sem horas.
--
-- A apuracao trabalha em minutos (a uniao dos intervalos das atividades), e e'
-- ela que converte: nenhuma tela pede minuto a quem raciocina em hora.
--
-- texto_evento_html / texto_atividade_html / texto_apresentacao_html: um
-- texto por tipo de certificado, escrito no editor rico, com as palavras
-- chave de CertificadoTextoService substituidas na geracao. O texto de
-- atividade serve a todas as atividades, por usar [[atividade.nome]] e
-- [[atividade.periodo]].
--
-- fundo_url / fundo_atividade_url / fundo_apresentacao_url: endereco de uma
-- imagem da Biblioteca de midia, escolhida no seletor da tela, que abre a
-- pasta "Fundo Certificados" (decisao do dono). A atividade pode ter fundo
-- proprio (evento_atividades.certificado_fundo_url, migration 202); em
-- branco nela, vale fundo_atividade_url daqui.
--
-- fundo_cor / fundo_atividade_cor / fundo_apresentacao_cor: cor de fundo da
-- folha, em notacao hexadecimal com o '#'. Vale quando o endereco da imagem
-- esta' em branco, que e' o que o botao de limpar do seletor faz. Nula
-- tambem, a folha sai branca - nenhuma cor inventada.
--
-- emissao_liberada_em: a chave do Administrador, que vale SO' PARA O
-- PARTICIPANTE. A outra metade da trava e' eventos.data_fim ja' passada, e
-- essa vale para todo mundo, inclusive para o Administrador: emitir antes do
-- fim congelaria carga horaria incompleta num documento que, por decisao do
-- dono, e' guardado e nunca mais muda.
CREATE TABLE IF NOT EXISTS evento_certificado_config (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 0,
    min_atividades SMALLINT UNSIGNED NULL,
    min_dias SMALLINT UNSIGNED NULL,
    min_horas SMALLINT UNSIGNED NULL,
    exige_credenciamento TINYINT(1) NOT NULL DEFAULT 0,
    carga_horaria_avaliador_horas SMALLINT UNSIGNED NULL,
    texto_evento_html MEDIUMTEXT NULL,
    texto_atividade_html MEDIUMTEXT NULL,
    texto_apresentacao_html MEDIUMTEXT NULL,
    fundo_url VARCHAR(255) NULL,
    fundo_atividade_url VARCHAR(255) NULL,
    fundo_apresentacao_url VARCHAR(255) NULL,
    fundo_cor VARCHAR(7) NULL,
    fundo_atividade_cor VARCHAR(7) NULL,
    fundo_apresentacao_cor VARCHAR(7) NULL,
    emissao_liberada_em DATETIME NULL,
    emissao_liberada_por INT UNSIGNED NULL,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_evento_certificado_config_evento (evento_id),
    CONSTRAINT fk_evento_certificado_config_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    CONSTRAINT fk_evento_certificado_config_liberada_por FOREIGN KEY (emissao_liberada_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
