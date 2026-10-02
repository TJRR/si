-- Fase 60 (pendencias 2 e 8): valor novo 'anais' no fim da lista de tipos de
-- secao da pagina do Evento, no molde da 179; os valores atuais ficam como
-- estao. Acrescentar no fim da lista nao reescreve a tabela.
ALTER TABLE evento_secoes_ordem
    MODIFY COLUMN tipo ENUM(
        'quadros','faixas','bloco','contagem','cronograma',
        'cartoes','destaques','programacao','faq','local','estandes','anais'
    ) NOT NULL;
