-- Fase 49: autoria de Trabalhos em tabela N-para-N, nao colunas singulares
-- de coautor em trabalhos - o motor precisa suportar qualquer quantidade
-- de autores configurada em evento_trabalhos_config.quantidade_maxima_autores,
-- sem exigir nova migration quando uma edicao futura mudar esse numero.
-- Exatamente 1 registro por trabalho_id tem eh_autor_principal = 1,
-- garantido em codigo (TrabalhoSubmissaoService), nao ha trava de banco
-- pratica para "exatamente 1 dentro de um grupo". usuario_id so e'
-- preenchido para o autor principal (sempre tem conta, exigencia da
-- Fase 50 mostrar resultado dentro do app); coautor nunca precisa de
-- conta, so os campos que a minuta pede no formulario (nome/cpf/email/
-- cargo/orgao de origem).
--
-- Fase 49B (correcao na fonte, nada disto chegou a producao): a versao
-- original tinha `vinculo_institucional` (texto livre unico) e um
-- comentario que afirmava "dados de pessoa nunca duplicados aqui", o que
-- nao era verdade - `cpf` e' de fato gravado aqui para TODO autor (nao ha
-- como evitar para coautor sem conta), e nada sincronizava de volta com
-- usuarios_perfil. Corrigido: `cargo`/`orgao_origem` substituem
-- `vinculo_institucional`, usando os MESMOS DOIS CAMPOS ESTRUTURADOS de
-- usuarios_perfil (e do formulario de inscricao do evento, que ja usava
-- esse formato desde a Fase 39) em vez de um texto livre proprio. Para o
-- AUTOR PRINCIPAL, TrabalhoSubmissaoService::submeter() sincroniza
-- cpf/cargo/orgao_origem de volta em usuarios_perfil a cada submissao -
-- essa tabela continua guardando sua propria copia (necessaria: retrato
-- historico da submissao E unico jeito de registrar coautor sem conta),
-- mas deixa de ser uma copia solta e desatualizavel.
CREATE TABLE IF NOT EXISTS trabalho_autores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trabalho_id INT UNSIGNED NOT NULL,
    eh_autor_principal TINYINT(1) NOT NULL DEFAULT 0,
    usuario_id INT UNSIGNED NULL,
    nome VARCHAR(150) NOT NULL,
    cpf VARCHAR(30) NOT NULL,
    email VARCHAR(150) NOT NULL,
    cargo VARCHAR(150) NULL,
    orgao_origem VARCHAR(150) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_trabalho_autores_trabalho FOREIGN KEY (trabalho_id) REFERENCES trabalhos (id),
    CONSTRAINT fk_trabalho_autores_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_trabalho_autores_cpf ON trabalho_autores (cpf);
