-- Fase 55 (28/09/2026): dados de contato e escolha de visibilidade no perfil
-- da PESSOA (usuarios_perfil, criada na migration 142). Com as Conexoes
-- (migration 181), o que estava aqui deixa de ser visto so' pelo Admin e
-- pode ser mostrado a outro participante - mas nunca sem a pessoa liberar,
-- campo a campo.
--
-- Aditivo: um unico ALTER TABLE com todos os ADD COLUMN juntos. Comando de
-- definicao de dados no MySQL confirma sozinho (o database/migrate.php abre
-- transacao, mas ela nao cobre DDL), entao concentrar tudo num comando so'
-- evita o arquivo ficar meio aplicado sem registro em migracoes_executadas.
--
-- telefone: guardado como a pessoa digitou, ja' validado no servidor por
-- formatarTelefoneBr() (app/helpers.php, Fase 51). Os enderecos de discagem
-- e de conversa sao montados na exibicao por linkTelefone()/linkWhatsApp(),
-- que normalizam com telefoneComCodigoPais() - nada disso e' gravado.
--
-- telefone_whatsapp: a propria pessoa marca que aquele numero recebe
-- mensagem no WhatsApp; sem a marca, so' aparece o discador.
--
-- redes_sociais: mapa rede -> endereco, no mesmo formato de
-- contatos_concurso.redes_sociais (migration 068). JSON aqui porque e' mapa
-- aberto de verdade; as chaves aceitas sao as cinco de
-- UsuarioPerfilRepository::REDES_SUPORTADAS. A pessoa digita so' o nome de
-- usuario e o endereco completo e' montado por
-- UsuarioPerfilRepository::normalizarEnderecoRede(), que tambem aceita o
-- endereco colado e recusa dominio de outra rede; na exibicao, passa de novo
-- por linkHttpValido() (mesmo cuidado que a Fase 49 exigiu no endereco do
-- trabalho: conteudo digitado por pessoa e mostrado a terceiros).
--
-- mostrar_*: seis marcas, todas 0 por padrao. Sao COLUNAS, e nao um campo
-- JSON de preferencias, para que toda linha que ja existe nasca fechada sem
-- depender de como cada ponto de leitura interpreta a ausencia de chave -
-- que e' exatamente onde dado pessoal vaza. Nome e e-mail nao tem marca:
-- aparecem sempre para quem se conectou, por decisao registrada na fase.
ALTER TABLE usuarios_perfil
    ADD COLUMN telefone VARCHAR(20) NULL,
    ADD COLUMN telefone_whatsapp TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN redes_sociais JSON NULL,
    ADD COLUMN mostrar_foto TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN mostrar_cargo TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN mostrar_orgao_origem TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN mostrar_minicurriculo TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN mostrar_telefone TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN mostrar_redes_sociais TINYINT(1) NOT NULL DEFAULT 0;
