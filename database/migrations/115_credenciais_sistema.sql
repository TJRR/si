-- Fase 35 (Parte C): credenciais de integracao saem de config/local.php e
-- passam a morar aqui, cifradas. Motivo concreto: o pacote de copia de
-- seguranca do codigo e o arquivo de exportacao do banco sao os dois
-- baixados por endereco publico durante a atualizacao. Enquanto o segredo
-- vive num arquivo dentro da pasta publicada, o pacote de codigo carrega o
-- segredo em texto puro; com ele cifrado no banco, nem o pacote de codigo
-- (que passa a levar so' a chave-mestra) nem a exportacao do banco (que
-- leva so' o texto cifrado) bastam sozinhos.
--
-- 'grupo' e 'chave' reproduzem a estrutura que ja existia no arquivo:
-- google_service_account.private_key, google_oauth.client_secret,
-- smtp.pass, e assim por diante. Sem tabela por integracao - integracao
-- nova entra como linha, nunca como migracao nova.
--
-- 'sigiloso' separa o que precisa ser cifrado do que nao precisa.
-- client_email, token_uri, host, port, from_email, from_name e redirect_uri
-- NAO sao segredo: ficam em texto legivel de proposito, porque poder ler
-- esses valores direto no banco ajuda no diagnostico e nao custa nada.
-- Cifrado mesmo so' private_key, client_secret e pass.
--
-- 'valor' e' TEXT (nao VARCHAR) por causa da chave privada PEM, que passa
-- de mil e setecentos caracteres antes de cifrar - e cresce depois.
--
-- atualizado_por sem ON DELETE: excluir um usuario nao pode apagar em
-- silencio o registro de quem trocou uma credencial.
CREATE TABLE IF NOT EXISTS credenciais_sistema (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    grupo VARCHAR(40) NOT NULL,
    chave VARCHAR(60) NOT NULL,
    valor TEXT NULL,
    sigiloso TINYINT(1) NOT NULL DEFAULT 1,
    atualizado_por INT UNSIGNED NULL,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_credenciais_usuario FOREIGN KEY (atualizado_por) REFERENCES usuarios (id),
    UNIQUE KEY uq_credencial (grupo, chave),
    KEY idx_credenciais_grupo (grupo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
