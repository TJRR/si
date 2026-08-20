-- Dois campos novos de Configuracoes > Contato, na mesma migration por
-- serem a mesma frente: tirar do codigo o que e' dado do orgao.
--
-- mapa_url: endereco do rodape vira link pro mapa. O ponto exato
-- (coordenadas, place id, link curto) nao da' pra derivar do texto livre do
-- endereco com confianca, entao e' campo proprio, preenchido pelo Admin
-- colando o link de compartilhamento do mapa. NULL = comportamento anterior
-- preservado: o endereco continua exibido como texto puro, sem virar link.
-- Validado com linkHttpValido() na gravacao e de novo na exibicao (mesma
-- convencao de link_meet e das redes sociais).
--
-- nome_organizador_assinatura: nome que assina os e-mails automaticos
-- (recuperacao de senha e acesso liberado). Estava fixo no codigo em
-- App\Services\NotificacaoService, junto com o e-mail e o telefone que esta
-- mesma tabela ja guardava. NULL = a linha simplesmente nao sai na
-- assinatura, igual as linhas de e-mail e telefone sem dado cadastrado.
ALTER TABLE contatos_concurso
    ADD COLUMN mapa_url VARCHAR(500) NULL AFTER endereco,
    ADD COLUMN nome_organizador_assinatura VARCHAR(150) NULL AFTER mapa_url;
