-- Fase 58: o canal a seguir em cada rede (dinamica de pontos v2: seguir o
-- "Pacoca Criativa" no YouTube, no Spotify e no Flickr), no lugar do texto
-- fixo "canal do Tribunal". Em branco, a tela continua com o texto
-- generico.
--
-- incluida: a rede foi escolhida pelo Administrador para este evento. Sem
-- ela, a tela de Configuracoes desenhava as dez redes da lista fechada uma
-- atras da outra, e salvarRedes() gravava as dez linhas a cada salvamento,
-- entao a tabela nao distinguia a rede escolhida da rede que ninguem tocou.
-- Padrao 0 de proposito: as linhas gravadas antes desta coluna nascem nao
-- incluidas, e a tela abre vazia, que e' o estado correto de um evento
-- ainda nao configurado. A rede retirada mantem pontos, limites e canal,
-- para voltar como estava se for incluida de novo.
ALTER TABLE evento_divulgacao_redes
    ADD COLUMN acompanhar_canal_nome VARCHAR(100) NULL AFTER acompanhar_prova,
    ADD COLUMN acompanhar_canal_endereco VARCHAR(255) NULL AFTER acompanhar_canal_nome,
    ADD COLUMN incluida TINYINT(1) NOT NULL DEFAULT 0 AFTER rede;
