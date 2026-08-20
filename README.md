# Sistema de Inovação (SI)

**Plataforma livre e completa para gerir prêmios, concursos e programas de inovação no setor público — do edital ao resultado publicado.**

Inscrição e homologação de equipes, formulários criados sem escrever código,
avaliação por critérios com sigilo cego, fórmula de pontuação configurável,
regras de desempate, mentorias e oficinas com agenda integrada, requerimentos
assinados digitalmente, portal institucional editável e transparência pública —
tudo pela própria interface, sem depender de programador a cada edição.

`Licença MIT` · `PHP 7.3+` · `MySQL 8` · `sem framework` · `sem bibliotecas JavaScript de terceiros` · `em produção`

Desenvolvido pelo **Núcleo de Projetos e Inovação (NPI) do Tribunal de Justiça do
Estado de Roraima (TJRR)** e disponibilizado sob licença MIT para que qualquer
tribunal, órgão público, universidade ou instituição possa usar, adaptar e
redistribuir livremente — inclusive com alterações próprias, sem pedir
autorização e sem custo.

Instância pública em operação: <https://npi.tjrr.jus.br>

---

## Sumário

- [Por que este sistema existe](#por-que-este-sistema-existe)
- [O que ele entrega, na prática](#o-que-ele-entrega-na-prática)
- [O princípio: um motor genérico configurável](#o-princípio-um-motor-genérico-configurável)
- [Avaliação: sigilo, designação e pontuação](#avaliação-sigilo-designação-e-pontuação)
- [Além do concurso: mentoria, oficina, dúvidas e requerimentos](#além-do-concurso-mentoria-oficina-dúvidas-e-requerimentos)
- [Portal institucional e transparência](#portal-institucional-e-transparência)
- [Integrações](#integrações)
- [Segurança e proteção de dados](#segurança-e-proteção-de-dados)
- [Arquitetura técnica](#arquitetura-técnica)
- [O sistema em números](#o-sistema-em-números)
- [Rodando localmente](#rodando-localmente)
- [Operação e manutenção](#operação-e-manutenção)
- [Convenções de interface](#convenções-de-interface)
- [Adotando o sistema no seu órgão](#adotando-o-sistema-no-seu-órgão)
- [Licença](#licença)
- [Histórico de evolução](#histórico-de-evolução)

---

## Por que este sistema existe

Prêmios de inovação no serviço público costumam ser tocados no improviso:
formulários avulsos, planilhas de notas circulando por e-mail, avaliadores que
sabem exatamente de quem é cada projeto, ranking calculado à mão, resultado
divulgado num PDF solto. Isso funciona uma vez. Não funciona na segunda edição,
não resiste a um questionamento e não deixa rastro auditável.

Este sistema foi construído para resolver isso de forma definitiva e reaproveitável:

- **Uma edição não é código.** Cada edição do prêmio — trilhas, temas, desafios,
  etapas, formulários, critérios, pesos, fórmula, desempate, prazos, premiação —
  é cadastrada pela interface administrativa. A 6ª edição não exige uma linha de
  programação a mais que a 5ª.
- **A lisura é estrutural, não é promessa.** Sigilo cego real (o avaliador vê
  "Equipe 7", nunca o nome), designação por sorteio auditável, trilha de
  auditoria em toda escrita no banco, relatório de conferência anonimizado.
- **O participante não fica no escuro.** Painel próprio, status de cada etapa,
  prazos, notas por avaliador anonimizado, canal de dúvidas com prazo de
  resposta, agenda de mentorias e oficinas.
- **A instituição não fica refém de fornecedor.** Código aberto sob MIT, sem
  framework, sem serviço pago obrigatório, sem dependência de nuvem para
  funcionar.

O sistema nasceu como fundação de dados e evoluiu ao longo de **fases
incrementais** até virar uma plataforma completa — cada fase entregue, testada e
publicada em produção durante uma edição real do Prêmio de Inovação, com equipes
reais, avaliadores reais e prazos reais.

---

## O que ele entrega, na prática

### Para quem participa

| Recurso | O que faz |
|---|---|
| Cadastro autoatendido | Qualquer pessoa se cadastra; a conta nasce pendente e só entra depois de aprovada pela organização |
| Inscrição de equipe | Formulário próprio por trilha, escolha de desafio, composição da equipe com líder e integrantes |
| Painel do participante | Etapas abertas, prazos, o que já foi enviado, o que falta, resultado de cada fase |
| Submissão por etapa | Formulário dinâmico com texto, seleção, arquivo (PDF), vídeo do YouTube e link externo |
| Notas e devolutiva | Após a publicação, o participante vê suas notas por avaliador anonimizado e o feedback recebido |
| Tira-Dúvidas | Canal formal de dúvidas com anexo, acompanhamento de status, reabertura e prazo de resposta |
| Requerimentos | Geração do documento a partir de um modelo oficial, assinatura digital externa e protocolo |
| Mentoria e Oficina | Reserva de horário de mentoria (exclusivo por equipe) e inscrição em oficinas (coletivas), com link da sala virtual |

### Para quem avalia

| Recurso | O que faz |
|---|---|
| Tela de avaliação por critério | Abas por critério, conteúdo integral da submissão ao lado, indicador granular do que já foi pontuado |
| Sigilo cego | Identificação por número estável de equipe, sem nome, sem instituição, sem pista de ordem de envio |
| Designação controlada | O avaliador vê exatamente o que lhe foi atribuído — nada além |
| Feedback estruturado | Devolutiva textual por submissão, publicada junto com o resultado |
| Progresso individual | Quanto falta para concluir cada etapa, por avaliador |

### Para quem organiza

| Recurso | O que faz |
|---|---|
| Construtor de formulários | Cria e ordena campos, define obrigatoriedade, tipos e validação — sem código, com ciclo rascunho → publicado → despublicado |
| Homologação de equipes | Fila de análise com aprovação, rejeição com motivo, mínimo de integrantes configurável e confirmação pública de participação |
| Critérios, pesos e fórmula | Critérios por etapa, pesos, fórmula de pontuação escrita livremente e regras de desempate ordenadas |
| Designação de avaliadores | Modo aberto, manual, automático ou sorteio aleatório restrito a categorias compatíveis |
| Apuração e resultado | Cálculo, ranking, classificação para a etapa seguinte, publicação controlada e reabertura de etapa |
| Auditoria | Registro de toda escrita (quem, quando, o quê, antes e depois), com busca e relatório em PDF anonimizado |
| Notificações e e-mail | Avisos no painel e por e-mail nos marcos do processo |
| Ajuda contextual | Texto de ajuda escrito para a tela em que a pessoa está, mais um glossário de conceitos transversais |
| Modo de manutenção | Tira o sistema do ar para todos, menos administradores, durante uma atualização |

---

## O princípio: um motor genérico configurável

Toda a modelagem parte de uma hierarquia única, que serve a qualquer edição
futura e a qualquer órgão:

```
Concurso (uma edição — ex.: "5º Prêmio de Inovação")
└── Trilha (segmento de público — ex.: Interna / Externa)
    ├── Tema (do edital) → Desafio (a pergunta que a equipe escolhe responder)
    └── Etapa (fase do processo, ordenada, com prazo próprio)
        ├── Formulário Dinâmico (campos configuráveis, sem código)
        │   └── Submissão (a resposta de uma equipe)
        ├── Critérios de Avaliação + Fórmula de Pontuação + Regras de Desempate
        └── Resultado da Etapa → Resultado da Trilha (ranking e publicação)
```

Nada nessa árvore está fixo no código. Quantidade de trilhas, nomes, ordem das
etapas, número de critérios, pesos, fórmula, prazos, premiação geral ou por
trilha — tudo é dado, cadastrado pela interface.

### Perfis de acesso

| Perfil | Alcance |
|---|---|
| **Administrador** | Acesso total à edição |
| **Suporte** | Leitura ampla e um conjunto restrito de ações operacionais |
| **Avaliador** | Somente o que lhe foi designado, dentro do concurso em que atua |
| **Participante** | Sua equipe, suas submissões, suas dúvidas e requerimentos |
| **Colaborador** | Pessoa externa que só enxerga dúvidas escaladas para ela — nenhum outro acesso administrativo |

Toda conta — criada manualmente ou via Google — nasce **pendente** e só consegue
entrar depois de aprovada por um Administrador. Não há exceção nesse caminho.

---

## Avaliação: sigilo, designação e pontuação

Cada Etapa carrega duas configurações independentes, sempre por etapa:

**Modo de sigilo**

- `cego` — o avaliador nunca vê equipe nem participante. Vê um número estável
  atribuído **uma única vez por etapa**, sorteado antes da numeração, e nunca
  recalculado a partir da ordem de submissão (justamente para não vazar quem
  enviou primeiro).
- `aberto` — sem restrição de identificação.

**Modo de designação**

- `aberto` — o avaliador escolhe o que avaliar.
- `manual` — o Administrador designa um a um.
- `automatico` — distribuição automática.
- `sorteio_categoria` — sorteio aleatório restrito a avaliadores de uma
  **Categoria de Avaliador** compatível com o critério, com controle de vagas.
  Uma designação de origem "sorteio" não pode ser removida pela interface, de
  propósito: preserva a lisura do sorteio já aceito.

**Pontuação**

A fórmula é escrita livremente pelo Administrador (expressão aritmética sobre os
critérios, avaliada por um interpretador próprio — sem `eval`), com fallback para
média ponderada pelos pesos. As regras de desempate são ordenadas e configuradas
por etapa.

**Publicação**

Cada etapa define seu próprio `mecanismo_avaliacao` e sua própria
`visibilidade_publica`: dá para ter uma etapa avaliada e não divulgada, uma etapa
divulgada integralmente e uma etapa em que só a lista de classificados aparece.
Depois do prazo ou da publicação, a submissão passa a somente-leitura
automaticamente.

---

## Além do concurso: mentoria, oficina, dúvidas e requerimentos

**Mentoria e Oficina** vivem fora da árvore Trilha/Etapa, com agenda própria:
mentoria é uma equipe por horário (reserva exclusiva); oficina é coletiva
(inscrição de várias equipes no mesmo horário). O link da sala virtual só aparece
para quem reservou ou está inscrito — nunca nas páginas públicas de
transparência. Cada horário pode ser **aberto a todos** ou **vinculado a uma
etapa**: nesse caso só enxerga e se inscreve quem está habilitado a ela, pelo
mesmo critério que libera a submissão — estar classificado na etapa anterior.
Horário só pode ser editado ou removido antes da data marcada. Quando a integração com o Google Agenda está ativa, cada horário
vira um evento real na agenda do organizador, com convite e RSVP dos
participantes; e, depois do encontro, o sistema busca a **presença real** de quem
entrou na sala e por quanto tempo.

**Tira-Dúvidas** é o canal formal do participante: dúvida com anexo, resposta,
reabertura, prazo de atendimento monitorado e escalonamento para um colaborador
externo quando a resposta depende de outra área.

**Modelos de Documento e Requerimentos** cobrem o que antes era feito por e-mail:
o Administrador escreve um modelo em editor rico usando marcações do tipo
`[[lider.nome]]`, `[[equipe.nome]]`, `[[desafio.titulo]]`; o líder da equipe gera
o PDF já preenchido, assina digitalmente fora do sistema (gov.br) e devolve o
arquivo assinado. O sistema então extrai do próprio PDF o titular declarado no
certificado e oferece uma verificação automática de apoio — sem jamais substituir
a conferência manual no site oficial, que continua obrigatória e registrada por
confirmação explícita do Administrador.

---

## Portal institucional e transparência

O site público não é uma página estática mantida por programador. É um módulo do
sistema, escopado por edição:

- **Slideshow, banners e blocos de texto rico**, com editor próprio e reordenação
  por arrastar-e-soltar
- **Prêmios, FAQ, cronograma com eventos avulsos e contato**
- **Documentos e editais versionados**, com controle de publicação
- **Biblioteca de mídia** compartilhada
- **Ordenação das seções da home** definida pelo Administrador
- **Edições anteriores** — repositório público das edições passadas
- **Identidade visual** (cores, logo, favicon) global ou por edição
- **Páginas públicas de transparência**: equipes homologadas, resultados
  publicados, agenda de mentorias e oficinas

Editor rico, reordenação por arrastar-e-soltar, máscaras, abas e árvore de
navegação são **JavaScript puro escrito no projeto** — nenhuma biblioteca de
terceiros, nenhum CDN, nenhum rastreador.

---

## Integrações

Todas são **opcionais**: o sistema funciona por completo sem nenhuma delas.

| Integração | Para quê | Como |
|---|---|---|
| **Login Google (OAuth2)** | Entrar com a conta institucional, sem senha nova | Fluxo *Authorization Code* implementado em cURL puro, sem SDK. Vínculo por e-mail exato, `state` aleatório de uso único contra CSRF, recusa de e-mail não verificado, **nenhum token armazenado** |
| **Google Agenda** | Cada mentoria/oficina vira evento real, com convite e RSVP | Service Account com delegação de domínio, impersonando o organizador; agenda secundária por edição; falha-suave (o sistema nunca trava se o Google estiver fora) |
| **Google Meet** | Relatório de presença real: quem entrou, por quanto tempo, quem entrou sem ser convidado | Leitura do registro da conferência após o encerramento, cruzando convidado → RSVP → presença |
| **Assinatura gov.br / ITI** | Conferência de requerimentos assinados digitalmente | Leitura do certificado embutido no PDF (parser DER/X.509 mínimo, escrito no projeto) + verificação automática de apoio |
| **SMTP** | Notificações por e-mail nos marcos do processo | PHPMailer |

---

## Segurança e proteção de dados

O sistema passou por uma **auditoria de segurança completa**, cujos achados foram
integralmente corrigidos e publicados. As defesas atuais:

**Autenticação e sessão**
- Senhas com `password_hash`/`password_verify` (bcrypt)
- Cookie de sessão `HttpOnly`, `Secure` sob HTTPS e `SameSite=Lax`
- Regeneração do identificador de sessão no login
- **Limite de tentativas de login** por janela de tempo
- **Mensagem de erro única** para e-mail inexistente, senha errada e conta
  pendente/rejeitada/suspensa — sem dar pista de qual é o caso real
- Recuperação de senha por token de uso único e prazo de validade

**Requisições**
- **Proteção CSRF centralizada no roteador**: todo `POST` é verificado antes de
  chegar ao controller, com comparação em tempo constante
- Cabeçalhos `X-Frame-Options`, `X-Content-Type-Options` e `Referrer-Policy`
- Autorização por perfil **e** por concurso em middleware, não espalhada nas telas

**Dados e arquivos**
- Acesso ao banco exclusivamente por PDO com *prepared statements*
- Upload valida o **tipo real do arquivo** (`finfo`), nunca o cabeçalho enviado
  pelo navegador, e bloqueia *path traversal* por caminho canônico
- Arquivos de submissão ficam em área privada, fora da árvore servida pela web
- Credenciais moram em arquivo PHP executável (nunca em texto plano), fora do
  controle de versão
- Guarda de inicialização: todo arquivo PHP que não seja o ponto de entrada
  recusa execução direta
- Scripts de linha de comando recusam ser chamados por HTTP

**Rastreabilidade e privacidade**
- **Auditoria de toda escrita**: ação, entidade, autor, data e conteúdo antes e
  depois, com busca e relatório em PDF
- Relatórios de conferência **anonimizados por linha** — impedem tanto a
  identificação da equipe quanto a reconstrução do conjunto de notas de uma
  pessoa
- **Política de retenção**: nomes capturados em relatórios de presença são
  anonimizados após 30 dias, preservando a contagem e a permanência para fins
  estatísticos, sem manter o dado pessoal
- Política de Privacidade e Termos de Serviço publicados como páginas próprias

---

## Arquitetura técnica

### Stack

PHP 7.3+ e MySQL 8, **sem framework**. Duas dependências de terceiros no total —
PHPMailer (e-mail) e Dompdf (relatórios em PDF). No navegador: **zero
bibliotecas**, zero CDN, zero build. Não há Node, npm, webpack, transpilação ou
etapa de compilação: o que está no repositório é o que roda.

A escolha é deliberada. Um sistema de tribunal precisa continuar funcionando
daqui a cinco anos, em servidor institucional, sem que a atualização de um
ecossistema de terceiros quebre a edição do prêmio no meio da avaliação.

### Roteamento sem dependência do servidor

O roteamento é por *query string* — `index.php?r=modulo/acao/parametro` — e não
exige `mod_rewrite`, `.htaccess`, `AllowOverride` nem `DocumentRoot` próprio.
Isso permite instalar o sistema como **subpasta de um portal institucional já
existente**, cenário comum no setor público, sem tocar na configuração do
servidor nem negociar acesso de infraestrutura.

O prefixo de instalação é centralizado numa única configuração e num helper de
URL: mudar de `/si` para qualquer outro caminho é alterar um valor.

### Camadas

```
si/
├── index.php                  # ponto de entrada único
├── politica.php / termos.php  # páginas legais (fora do roteador)
├── app/
│   ├── Core/                  # Router, Database (PDO), Auth, Auditoria, View,
│   │                          # Controller, GoogleOAuth, GoogleServiceAccountAuth,
│   │                          # ExpressaoAritmetica, Mailer, Texto
│   ├── Controllers/           # 58 controllers (administrativos, do participante,
│   │                          # do avaliador e públicos)
│   ├── Middleware/            # autorização por perfil e por concurso
│   ├── Repositories/          # 56 repositórios — 1 por entidade, sempre PDO
│   │                          # preparado + registro de auditoria
│   ├── Services/              # 28 serviços — regras de negócio (avaliação,
│   │                          # inscrição, uploads, Google, PDF, SLA, notificação)
│   ├── Validation/            # CPF, YouTube, upload de PDF
│   ├── Ajuda/                 # ajuda contextual: 1 arquivo por tela + conceitos
│   └── Views/                 # 141 telas (admin, participante, avaliador, público)
├── assets/
│   ├── css/site.css           # folha de estilo única do projeto
│   ├── js/                    # JavaScript puro, 1 arquivo por funcionalidade
│   └── uploads/               # mídia pública enviada pelo admin
├── config/                    # configuração da aplicação; credenciais locais
│                              # ficam fora do controle de versão
├── database/
│   ├── migrate.php            # aplicador de migrations
│   ├── migrations/            # 113 migrations numeradas e idempotentes
│   └── *.php                  # scripts operacionais de linha de comando
├── storage/                   # submissões privadas, logs e sessões
└── docker-compose.yml         # ambiente de desenvolvimento
```

### Padrões que valem para o projeto inteiro

- **Um repositório por entidade.** Nenhum SQL solto em controller ou view.
- **Auditoria na origem.** O registro de auditoria acontece dentro do
  repositório, não no controller — não há caminho de escrita que escape dele.
- **Serviços para regra compartilhada.** Quando uma regra passa a ser usada em
  dois lugares, ela vira serviço; não se duplica lógica de negócio.
- **Migrations numeradas e idempotentes.** Rodar de novo é seguro; o controle é
  por tabela própria.
- **Comentário explica *por quê*, não *o quê*.** O código carrega o histórico das
  decisões — inclusive as recusadas e o motivo — em português.
- **Todo script de manutenção é *dry-run* por padrão.** Nada escreve sem
  `--confirmar` explícito.

---

## O sistema em números

| | |
|---|---|
| Linhas de PHP no projeto | ~46.000 |
| Controllers | 58 |
| Repositórios | 56 |
| Serviços de domínio | 28 |
| Telas (views) | 141 |
| Tabelas no banco | 63 |
| Migrations | 113 |
| Telas com ajuda contextual escrita | ~110 (+ 10 conceitos transversais) |
| Scripts operacionais de linha de comando | 40 |
| Folha de estilo | ~3.900 linhas, sem framework CSS |
| JavaScript | ~1.800 linhas, sem biblioteca externa |
| Dependências de terceiros | 2 (PHPMailer, Dompdf) |
| Fases de desenvolvimento entregues | 34 |

---

## Rodando localmente

Requisitos: Docker e Docker Compose.

```bash
docker compose up -d --build
```

Sobe dois contêineres:

- `app` — PHP 7.3 + Apache em <http://localhost:8090>
- `db` — MySQL 8

Na primeira execução, crie o arquivo de credenciais locais a partir do exemplo:

```bash
cp config/local.example.php config/local.php
```

Os valores padrão já correspondem ao `docker-compose.yml`. Esse arquivo nunca é
versionado.

Aplique as migrations:

```bash
docker compose exec app php database/migrate.php
```

Crie o primeiro Administrador (prompt interativo — é o único caminho para criar
um administrador, já que todo outro cadastro nasce pendente):

```bash
docker compose exec app php database/seed_admin.php
```

Pronto: acesse <http://localhost:8090>, entre com a conta criada e cadastre o
primeiro Concurso.

### Habilitando as integrações Google (opcional)

Preencha a seção `google` de `config/local.php` com o Client ID e o Client Secret
de um projeto do Google Cloud Console e registre a URI de retorno no formato:

```
https://SEU-DOMINIO/CAMINHO-DA-INSTALACAO/index.php?r=auth/googleCallback
```

Para Agenda e Meet, configure adicionalmente uma Service Account com delegação de
domínio no Google Workspace do órgão. Sem isso, os módulos continuam funcionando
— apenas sem evento na agenda e sem relatório de presença.

---

## Operação e manutenção

### Scripts de linha de comando

Todos os scripts em `database/` seguem o mesmo contrato:

1. **Só rodam por linha de comando** — chamadas HTTP são recusadas.
2. **São *dry-run* por padrão** — mostram o que fariam, sem gravar nada.
3. **Só gravam com `--confirmar`.**

O procedimento correto é sempre executar primeiro sem a flag, conferir a saída, e
só então repetir com `--confirmar`.

| Grupo | Scripts | Para quê |
|---|---|---|
| Infraestrutura | `migrate`, `seed_admin`, `seed_formularios_inscricao` | Preparar o ambiente e a primeira edição |
| Equipes e integrantes | `gerenciar_membro_equipe`, `renomear_equipe`, `migrar_equipe_trilha` | Retificações de composição e de trilha, sem mexer no banco à mão |
| Submissões | `diagnosticar_submissoes_equipe`, `remover_submissoes`, `reabrir_formulario_edicao`, `importar_submissoes_google_forms` | Diagnóstico, remoção controlada por id explícito e importação de respostas externas |
| Avaliação | `diagnosticar_notas_avaliador`, `limpar_notas_avaliador`, `limpar_notas_teste`, `limpar_designacoes_etapa`, `limpar_avaliadores_fantasma`, `atribuir_numeros_sigilo_etapa` | Conferência e correção do processo avaliativo |
| Disponibilidade | `desativar_sistema`, `reativar_sistema` | Modo de manutenção pela linha de comando |
| Google | `capturar_presenca_google_meet`, `backfill_conference_id`, `testar_google_calendar`, `testar_google_meet_presenca` | Captura de presença, retrofit e diagnóstico das integrações |
| Privacidade | `expurgar_nomes_presenca` | Aplicação da política de retenção de 30 dias |
| Conteúdo | `migrar_conteudo_home`, `atualizar_icones_temas_desafios` | Migração de conteúdo legado e enriquecimento visual |

Cada script documenta no próprio cabeçalho o que faz, o que se recusa a fazer e
por quê. Vários têm travas deliberadas — remover submissão que já tem nota
lançada, excluir usuário com notas, desfazer designação de sorteio — que só podem
ser vencidas com uma flag explícita e adicional.

### Modo de manutenção

Uma verificação central no roteador, aplicada a **toda** requisição — autenticada
ou não — antes de resolver a rota. Com o modo ligado:

- Todos são bloqueados, exceto quem já está autenticado como **administrador**.
  Sessões abertas de outros perfis são encerradas na próxima ação.
- É exibida uma página de manutenção com HTTP 503, **sem CSS nem JS externo** —
  para nunca depender de algo que possa estar no meio de uma atualização.

Liga e desliga pelo botão em *Configurações* ou pelos scripts de linha de comando
equivalentes — que funcionam por acesso direto ao banco, servindo de interruptor
de emergência mesmo que a interface administrativa esteja inacessível.

O procedimento recomendado para atualizações que mexem em esquema ou em
comportamento de avaliação: desativar → atualizar código → rodar migrations →
testar como administrador → reativar.

### Processo agendado

O agendador do servidor executa periodicamente a captura de presença nas salas virtuais.

Consequências práticas para quem opera:

- **Uma atualização pode interromper um processo em andamento.** O script é
  seguro de interromper: processa um lote limitado por execução, grava de forma
  idempotente (reprocessar nunca duplica) e tem trava contra execuções
  sobrepostas.
- **Falha de processo agendado é silenciosa por natureza** — ninguém está olhando
  na hora em que ele roda. Por isso o próprio script notifica os administradores
  no painel quando esgota as tentativas de um horário, e registra log a cada
  execução.

---

## Convenções de interface

**Cor de mensagem é semântica em todo o sistema, sem exceção:**

| Cor | Significado | Quando usar |
|---|---|---|
| **Verde** | Sucesso | A ação foi concluída (*"Horário removido."*, *"Perfil atualizado."*) |
| **Laranja** | Alerta | Aviso ou estado intermediário — não é erro nem confirmação plena (*"Nenhuma submissão encontrada ainda."*, status pendente) |
| **Vermelho** | Erro | Falha real, ação não realizada, algo que impede o fluxo (validação rejeitada, exceção capturada) |

Vale tanto para texto quanto para os selos de status. Ao criar uma tela ou uma
mensagem nova, classifique pelo **significado real da mensagem** — nunca pela
"cor padrão" do bloco onde ela aparece.

---

## Adotando o sistema no seu órgão

O sistema foi escrito para ser adotado, não apenas lido. Se o seu tribunal,
universidade, secretaria ou instituto promove — ou quer promover — um prêmio,
hackathon, edital de inovação ou processo seletivo por etapas, ele já cobre o
ciclo inteiro.

**O que você precisa**

- PHP 7.3 ou superior com PDO/MySQL, cURL, `finfo`, `mbstring` e GD
- MySQL 8
- Um servidor SMTP para notificações
- *Opcional:* projeto no Google Cloud (login, agenda, presença) e agendador de
  tarefas do sistema operacional

**O que você não precisa**

- Domínio dedicado, `DocumentRoot` próprio ou `mod_rewrite` — o sistema roda como
  subpasta de um portal já existente
- Node.js, npm ou qualquer etapa de build
- Licença, contrato, autorização prévia ou aviso ao TJRR
- Fornecedor: não há componente proprietário, serviço pago obrigatório nem
  dependência de nuvem para o funcionamento básico

**Como começar**

1. Clone o repositório e suba o ambiente local (seção acima).
2. Rode as migrations e crie o primeiro Administrador.
3. Cadastre um **Concurso**, suas **Trilhas**, **Temas/Desafios** e **Etapas**.
4. Monte os **Formulários** de inscrição e de cada etapa pelo construtor.
5. Defina **Critérios**, pesos, **Fórmula** e **Desempate** por etapa.
6. Ajuste a **Identidade Visual** e monte a home pelo painel de conteúdo.
7. Publique.

Nada disso exige tocar no código. Se algo no seu edital não couber na
configuração existente, esse é exatamente o tipo de contribuição que o projeto
espera receber.

**Contribuições, dúvidas e adaptações** são bem-vindas. A licença MIT permite
fork livre — mas a comunidade de órgãos públicos ganha mais se as melhorias
voltarem para cá.

---

## Licença

[MIT](LICENSE) — Copyright (c) 2026 Tribunal de Justiça do Estado de Roraima.

Uso, cópia, modificação, publicação, distribuição, sublicenciamento e venda
permitidos, mantido o aviso de copyright. O software é fornecido "como está", sem
garantias.

---

## Histórico de evolução

Trinta e quatro fases, cada uma entregue e publicada em produção durante uma edição
real do prêmio.

| Fase | Entrega |
|---|---|
| 1 | Fundação: modelo de dados, autenticação, login Google |
| 2 | Concurso / Trilha / Etapa / Formulários Dinâmicos |
| 3 | Importação por linha de comando, tela de suporte, CMS leve, identidade visual |
| 4 | Notificação por e-mail, editor de fórmula livre |
| 5–6 | Refinamento visual, motor de avaliação, fluxo real de inscrição e homologação |
| 7–8 | Navegação em árvore do painel, tela de Usuários |
| 9 | Fórmula ponderada automática, conteúdo da submissão visível ao avaliador |
| 10 | Categorias de avaliador, sorteio automático de designação |
| 11 | Redesenho da tela do avaliador |
| 12 | Notificações do painel, trava de classificação entre etapas |
| 13 | Importação de respostas externas, página pública de resultados |
| 14 | **Publicação em produção**, auditoria, Configurações, Meu Perfil |
| 15–16 | Correções pós-publicação, gestão de convites |
| 17 | Correções amplas, retificação de dados reais |
| 18 | **Painel de conteúdo institucional completo**: home dinâmica por edição (slideshow, banners, blocos ricos, prêmios, FAQ, documentos versionados, biblioteca de mídia, cronograma, contato), repositório de Edições Anteriores, editor rico e reordenação por arrastar-e-soltar 100% próprios |
| 19 | Configuração global do site, cabeçalho configurável, ordenação da home, homologação pública, primeira versão das Mentorias |
| 20 | Cabeçalho com imagem, grade de posicionamento, etapas pendentes do avaliador, desempate por etapa |
| 21 | **Categorias de Avaliador**: seleção prévia ao sorteio, convite retroativo, diagnóstico de submissão duplicada |
| 22 | Recuperação de senha ("Esqueci minha senha"), correções de suporte |
| 23 | **Divulgação pública configurável por etapa**, relatório de auditoria em PDF anonimizado por linha |
| 24 | Progresso de avaliação por avaliador, **Mentoria e Oficina** com agenda e sala virtual, premiação geral vs. por trilha |
| 25 | **Modo de manutenção**, numeração de equipe estável sob sigilo cego, campo de link externo |
| 26 | Busca da auditoria estendida, relatório de notas por equipe em PDF |
| 27 | Correção de duas vulnerabilidades reais, visualização somente-leitura pós-prazo, notas por avaliador anonimizado no painel do participante, prazos com hora |
| 28 | Layout compartilhado do avaliador, correções de acesso do perfil Suporte, filtros de status |
| 29 | **Tira-Dúvidas** com prazo de atendimento e escalonamento, perfil Colaborador, convite de acesso por notificação |
| 30 | **Modelos de Documento** com marcações `[[palavra.chave]]` e **Requerimentos** com assinatura gov.br e verificação automática de apoio |
| 31 | **Integração com Google Agenda** (Service Account + delegação de domínio, RSVP), **ajuda contextual** em toda a plataforma, **auditoria de segurança completa** (CSRF central, limite de tentativas de login, cabeçalhos HTTP, correções de controle de acesso) |
| 32 | **Relatório de presença real nas salas virtuais** por horário de Mentoria/Oficina (quem entrou, por quanto tempo, quem entrou sem convite), cruzando convidado → RSVP → presença; **primeiro processo agendado do projeto**; política de retenção de 30 dias para os nomes capturados |
| 33 | Dados do órgão saem do código para Configurações (contato, mapa, assinatura de e-mail), README reescrito sem dados de infraestrutura, validação de link aplicada às redes sociais, Dúvidas e Requerimentos em popup, troca da própria senha |
| 34 | **Vínculo de compromisso com etapa**: mentoria e oficina restritas a quem está habilitado à etapa, edição de horário com trava por data, correções de layout do cabeçalho em telas pequenas |

---

<div align="center">

**Núcleo de Projetos e Inovação — Tribunal de Justiça do Estado de Roraima**

</div>
