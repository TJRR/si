-- Fase 59: plano de fundo proprio do certificado de UMA atividade (decisao do
-- dono: "o admin escolhe usar um padrao ja' existente no sistema ou enviar
-- para o banco um especifico para aquela atividade"). O banco de planos de
-- fundo e' a Biblioteca de midia, e o que se guarda aqui e' o endereco da
-- imagem escolhida.
--
-- Uma instrucao por arquivo, mesma regra da 185.
--
-- Em branco significa HERDA: vale evento_certificado_config
-- .fundo_atividade_url, e em branco nos dois o documento sai sem arte. Nunca
-- um fundo inventado.
--
-- certificado_fundo_cor acompanha pelo mesmo motivo: o seletor de fundo e' um
-- componente so' em todas as telas de certificado (imagem, botao de limpar e
-- cor), e deixar a atividade com metade dele seria duas interfaces para a
-- mesma decisao. Vale quando o endereco da imagem esta' em branco.
--
-- A coluna fica em evento_atividades, e nao numa tabela propria, pelo mesmo
-- criterio de tolerancia_presenca_efetiva (138) e dos pontos de presenca
-- (193): e' um atributo da atividade, um valor por atividade, sem historico.
ALTER TABLE evento_atividades
    ADD COLUMN certificado_fundo_url VARCHAR(255) NULL AFTER emite_certificado,
    ADD COLUMN certificado_fundo_cor VARCHAR(7) NULL AFTER certificado_fundo_url;
