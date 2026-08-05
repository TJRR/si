-- Fase 24 (bug reportado): premiacao cadastrada so' pensava no concurso como
-- um todo, mas o premio pode ser distinto por trilha (caso real do 5o
-- Premio, que tem 2 trilhas). modo_premiacao decide como a tela
-- Admin > Premiacao e a home publica exibem a lista: 'geral' (1 lista pro
-- concurso inteiro, comportamento original da Fase 18) ou 'por_trilha' (1
-- lista de 1o/2o/3o lugar POR trilha). trilha_id fica NULL quando o premio
-- foi cadastrado em modo 'geral'.
ALTER TABLE concursos ADD COLUMN modo_premiacao ENUM('geral', 'por_trilha') NOT NULL DEFAULT 'geral' AFTER status;
ALTER TABLE premios ADD COLUMN trilha_id INT UNSIGNED NULL AFTER concurso_id;
ALTER TABLE premios ADD CONSTRAINT fk_premios_trilha FOREIGN KEY (trilha_id) REFERENCES trilhas (id);
