# Sistema de Inovação (SI)

**Plataforma livre e completa para gerir prêmios, concursos e programas de inovação no setor público — do edital ao resultado publicado.**

Inscrição e homologação de equipes, formulários criados sem escrever código,
avaliação por critérios com sigilo cego, fórmula de pontuação configurável,
regras de desempate, mentorias e oficinas com agenda integrada, requerimentos
assinados digitalmente, portal institucional editável e transparência pública —
tudo pela própria interface, sem depender de programador a cada edição.

`Licença MIT` · `PHP` · `MySQL` · `em produção`

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
  serviço pago obrigatório, sem dependência de nuvem para funcionar.

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
| Comparação entre etapas | Um critério pode vincular uma ou mais etapas já realizadas da mesma trilha; a submissão correspondente da mesma equipe abre num popup, sem sair da tela de avaliação |
| Sigilo cego | Identificação por número estável de equipe, sem nome, sem instituição, sem pista de ordem de envio |
| Designação controlada | O avaliador vê exatamente o que lhe foi atribuído — nada além |
| Feedback estruturado | Devolutiva textual por submissão, publicada junto com o resultado |
| Progresso individual | Quanto falta para concluir cada etapa, por avaliador |

### Para quem organiza

| Recurso | O que faz |
|---|---|
| Construtor de formulários | Cria e ordena campos, define obrigatoriedade, tipos e validação — sem código, com ciclo rascunho → publicado → despublicado |
| Homologação de equipes | Fila de análise com aprovação, rejeição com motivo, mínimo de integrantes configurável, confirmação pública de participação e histórico completo de cada vínculo |
| Critérios, pesos e fórmula | Critérios por etapa, pesos, fórmula de pontuação escrita livremente e regras de desempate ordenadas |
| Designação de avaliadores | Modo aberto, manual, automático ou sorteio aleatório restrito a categorias compatíveis |
| Apuração e resultado | Cálculo, ranking, classificação para a etapa seguinte, publicação controlada e reabertura de etapa |
| Auditoria | Registro de toda escrita (quem, quando, o quê), com busca e relatório em PDF anonimizado |
| Notificações e e-mail | Avisos no painel e por e-mail nos marcos do processo |
| Ajuda contextual | Texto de ajuda escrito para a tela em que a pessoa está, mais um glossário de conceitos transversais |
| Modo de manutenção | Tira o sistema do ar para todos, menos administradores, durante uma atualização |
| Conteúdo institucional | Home, edições anteriores, prêmios, FAQ, documentos, cronograma e identidade visual editáveis pela própria interface, sem depender de programador |

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
| **Inscrito** | Quem se inscreveu num Evento (ex.: Semana de Inovação) — só o próprio painel do evento, auto-aprovado, sem depender de aprovação manual |

Toda conta — criada manualmente ou via Google — nasce **pendente** e só consegue
entrar depois de aprovada por um Administrador, com uma única exceção: quem se
inscreve num Evento (perfil Inscrito) já nasce aprovada automaticamente, pensado
para um evento aberto ao público em escala, onde aprovação manual não seria
viável — sem interferir na fila de aprovação de quem busca os demais perfis.

---

## Avaliação: sigilo, designação e pontuação

Cada Etapa carrega duas configurações independentes, sempre por etapa:

**Modo de sigilo**

- `cego` — o avaliador nunca vê equipe nem participante. Vê um número estável
  atribuído **uma única vez por etapa**, sorteado antes da numeração, e nunca
  recalculado a partir da ordem de submissão.
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
critérios), com fallback para média ponderada pelos pesos. As regras de
desempate são ordenadas e configuradas por etapa.

**Comparação entre etapas**

Um critério pode exigir mais do que o conteúdo da etapa atual — por exemplo,
julgar a "evolução do conceito" de uma equipe exige ver o que ela submeteu numa
etapa anterior. O Administrador vincula, por critério, uma ou mais etapas já
realizadas da mesma trilha; o avaliador vê um ícone por etapa vinculada, que
abre a submissão correspondente da mesma equipe num popup, sem quebrar o
sigilo cego.

**Publicação**

Cada etapa define sua própria visibilidade pública: dá para ter uma etapa
avaliada e não divulgada, uma etapa divulgada integralmente e uma etapa em que
só a lista de classificados aparece. Depois do prazo ou da publicação, a
submissão passa a somente-leitura automaticamente.

---

## Além do concurso: mentoria, oficina, dúvidas e requerimentos

**Mentoria e Oficina** vivem fora da árvore Trilha/Etapa, com agenda própria:
mentoria é uma equipe por horário (reserva exclusiva); oficina é coletiva
(inscrição de várias equipes no mesmo horário). O link da sala virtual só aparece
para quem reservou ou está inscrito. Cada horário pode ser **aberto a todos**
ou **vinculado a uma etapa**, restringindo quem enxerga e se inscreve. Quando a
integração com o Google Agenda está ativa, cada horário vira um evento real na
agenda do organizador, com convite e confirmação dos participantes; e, depois
do encontro, o sistema busca a **presença real** de quem entrou na sala e por
quanto tempo.

**Tira-Dúvidas** é o canal formal do participante: dúvida com anexo, resposta,
reabertura, prazo de atendimento monitorado e escalonamento para um colaborador
externo quando a resposta depende de outra área. Uma dúvida já respondida
costuma valer para todo mundo: Administrador e Suporte podem **aproveitá-la
como pergunta frequente**, publicando-a no banco de FAQ com o texto reescrito
em termos genéricos — a dúvida original permanece intacta e exclusiva da equipe
que perguntou.

**Modelos de Documento e Requerimentos** cobrem o que antes era feito por e-mail:
o Administrador escreve um modelo em editor rico usando marcações do tipo
`[[lider.nome]]`, `[[equipe.nome]]`, `[[desafio.titulo]]`; o líder da equipe gera
o PDF já preenchido, assina digitalmente fora do sistema (gov.br) e devolve o
arquivo assinado. O sistema oferece uma verificação automática de apoio — sem
jamais substituir a conferência manual, que continua obrigatória e registrada
por confirmação explícita do Administrador.

**Semana de Inovação** é uma entidade nova, com o mesmo status estrutural do
Concurso (cadastro próprio, sem nenhum vínculo com a edição em andamento do
Prêmio de Inovação), divulgação em destaque na home e inscrição pública com
acesso próprio — quem ainda não tem conta entra com Google ou cria um
cadastro dedicado, ambos com liberação imediata, sem espera por aprovação
manual (adequado ao volume de um evento aberto ao público). Quem se inscreve
passa a acessar o evento por um **aplicativo web instalável** — mesmo link,
mesma experiência no celular e no computador; "instalar" (adicionar à tela
inicial/área de trabalho) é sempre um atalho opcional, nunca obrigatório.
Cada inscrição gera um **crachá digital de credenciamento**, com código
único pronto para impressão, disponível assim que a pessoa se inscreve. O
próprio aplicativo lê esse código pela câmera do celular (quando o
navegador suportar) ou por digitação manual, sempre pelo próprio
participante — não existe leitura feita pela equipe organizadora. A
confirmação de inscrição chega por e-mail e também como notificação dentro
do próprio aplicativo. O evento tem sua própria **agenda de atividades**
(cursos, palestras, seminários), cada uma com inscrição e vagas opcionais —
limitadas ou não, com ou sem lista de espera, à escolha de quem organiza —
e emissão de certificado configurável por atividade. Cada atividade tem
também um código fixo próprio, impresso e afixado no espaço onde ela
acontece: o participante confirma presença apontando a câmera para esse
código, sempre por conta própria. Uma atividade pode ser presencial, online
ou híbrida: quando aceita participação online, ganha um segundo código,
próprio para essa forma de participação. Cada atividade também pode ter um
ou mais **Facilitadores** (instrutor, professor, palestrante) vinculados,
sempre alguém já cadastrado no sistema. A lista de inscritos, do evento
inteiro ou de uma atividade específica, pode ser exportada no formato
exigido por sistemas parceiros de certificação.

Dentro da Semana de Inovação, **Trabalhos** organiza a submissão e a
avaliação de artigos e resumos expandidos, com motor próprio (sem nenhuma
relação com o Concurso): o autor envia o texto pelo próprio aplicativo do
evento, com autor principal e coautores; a organização define os critérios
de nota e convida avaliadores avulsos, que analisam cada trabalho às cegas
(sem saber quem é o autor) e lançam a nota por critério; o sistema calcula
o resultado final, aplica desempate quando necessário e seleciona os
trabalhos aprovados.

---

## Portal institucional e transparência

O site público não é uma página estática mantida por programador. É um módulo do
sistema, escopado por edição:

- **Apresentação de slides, faixas e blocos de texto rico**, com editor próprio e reordenação
  por arrastar-e-soltar
- **Prêmios, FAQ, cronograma com eventos avulsos e contato**
- **Documentos e editais versionados**, com controle de publicação
- **Biblioteca de mídia** compartilhada
- **Ordenação das seções da home** definida pelo Administrador
- **Edições anteriores** — repositório público das edições passadas, com bloco de conteúdo rico opcional específico de cada edição
- **Identidade visual** (cores, logo, favicon) global ou por edição
- **Páginas públicas de transparência**: equipes homologadas, resultados
  publicados, agenda de mentorias e oficinas

Editor rico, reordenação por arrastar-e-soltar e árvore de navegação foram
escritos no próprio projeto — sem dependência de biblioteca externa nem de
serviço de terceiros para funcionar.

---

## Integrações

Todas são **opcionais**: o sistema funciona por completo sem nenhuma delas.

| Integração | Para quê |
|---|---|
| **Login Google** | Entrar com a conta institucional, sem senha nova |
| **Google Agenda** | Cada mentoria/oficina vira evento real, com convite e confirmação |
| **Google Meet** | Relatório de presença real: quem entrou, por quanto tempo |
| **Assinatura gov.br / ITI** | Conferência automática de apoio para requerimentos assinados digitalmente |
| **SMTP** | Notificações por e-mail nos marcos do processo |

---

## Segurança e proteção de dados

O sistema passou por uma auditoria de segurança completa, com os achados
integralmente corrigidos e publicados. Alguns pilares:

- Autenticação com limite de tentativas, sessão protegida e recuperação de
  senha por link de uso único.
- Proteção contra falsificação de requisição em toda ação de escrita.
- Autorização por perfil **e** por concurso, verificada a cada ação — não só
  no momento do login.
- Credenciais de integração (Google, e-mail) guardadas cifradas, nunca
  visíveis na tela mesmo para quem administra, com aviso automático aos demais
  administradores sempre que alguém acessa ou altera uma.
- Upload de arquivo validado pelo conteúdo real, não pela extensão informada
  pelo navegador.
- Auditoria completa de toda escrita relevante, com relatórios anonimizados
  quando o dado é sensível.
- Política de retenção de dados pessoais (ex.: nomes capturados em relatório
  de presença são anonimizados após 30 dias).

Detalhes de implementação dessas defesas não são divulgados por aqui.

---

## Adotando o sistema no seu órgão

O sistema foi escrito para ser adotado, não apenas lido. Se o seu tribunal,
universidade, secretaria ou instituto promove — ou quer promover — um prêmio,
hackathon, edital de inovação ou processo seletivo por etapas, ele já cobre o
ciclo inteiro.

**O que você precisa**

- Um servidor web com PHP e MySQL
- Um servidor SMTP para notificações
- *Opcional:* um projeto no Google Cloud, para login/agenda/presença

**O que você não precisa**

- Domínio dedicado nem estrutura própria de servidor — o sistema roda como
  subpasta de um portal já existente
- Licença, contrato, autorização prévia ou aviso ao TJRR
- Fornecedor: não há componente proprietário nem serviço pago obrigatório

**Como começar**

1. Suba o sistema no seu ambiente e crie o primeiro Administrador.
2. Cadastre um **Concurso**, suas **Trilhas**, **Temas/Desafios** e **Etapas**.
3. Monte os **Formulários** de inscrição e de cada etapa pelo construtor.
4. Defina **Critérios**, pesos, **Fórmula** e **Desempate** por etapa.
5. Ajuste a **Identidade Institucional** (nome e sigla do seu órgão) e a
   **Identidade Visual**, e monte a home pelo painel de conteúdo.
6. Publique.

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

Quase cinquenta fases, a maioria já publicada em produção durante uma edição
real do prêmio; o bloco de Eventos/Trabalhos (Fases 39 a 49) foi desenvolvido
e testado localmente ao longo do caminho, com o primeiro deploy de tudo esse
bloco de uma vez, junto com a Fase 49.

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
| 18 | **Painel de conteúdo institucional completo**: home dinâmica por edição, repositório de Edições Anteriores, editor rico e reordenação por arrastar-e-soltar |
| 19 | Configuração global do site, cabeçalho configurável, ordenação da home, homologação pública, primeira versão das Mentorias |
| 20 | Cabeçalho com imagem, etapas pendentes do avaliador, desempate por etapa |
| 21 | **Categorias de Avaliador**: seleção prévia ao sorteio, convite retroativo |
| 22 | Recuperação de senha ("Esqueci minha senha"), correções de suporte |
| 23 | **Divulgação pública configurável por etapa**, relatório de auditoria em PDF anonimizado |
| 24 | Progresso de avaliação por avaliador, **Mentoria e Oficina** com agenda e sala virtual, premiação geral vs. por trilha |
| 25 | **Modo de manutenção**, numeração de equipe estável sob sigilo cego, campo de link externo |
| 26 | Busca da auditoria estendida, relatório de notas por equipe em PDF |
| 27 | Correção de vulnerabilidades, visualização somente-leitura pós-prazo, notas por avaliador anonimizado no painel do participante |
| 28 | Layout compartilhado do avaliador, correções de acesso do perfil Suporte |
| 29 | **Tira-Dúvidas** com prazo de atendimento e escalonamento, perfil Colaborador |
| 30 | **Modelos de Documento** com marcações `[[palavra.chave]]` e **Requerimentos** com assinatura gov.br |
| 31 | **Integração com Google Agenda**, **ajuda contextual** em toda a plataforma, **auditoria de segurança completa** |
| 32 | **Relatório de presença real nas salas virtuais**, primeiro processo agendado do projeto, retenção de dados de 30 dias |
| 33 | Dados do órgão saem do código para Configurações, validação de link nas redes sociais |
| 34 | **Vínculo de compromisso com etapa**: mentoria e oficina restritas a quem está habilitado à etapa |
| 35 | Dúvida frequente vira pergunta publicável (FAQ) direto do canal de atendimento, **credenciais de integração cifradas em repouso** |
| 36 | **Correção de controle de acesso**: participante rejeitado após já ter sido homologado perde acesso a mentoria, oficina e requerimento, **histórico de homologação** por vínculo |
| 37 | **Comparação entre etapas na avaliação**: um critério pode vincular uma ou mais etapas já realizadas da mesma trilha |
| 38 | **Bloco de conteúdo opcional por edição** na página pública de Edições Anteriores, **descrição do concurso em texto rico** |
| 38A | Casas decimais configuráveis na exibição de notas, correção do cálculo da Nota Final da trilha e do desempate em caso de empate |
| 38B | **Agendamento de apresentação de pitch**: equipes finalistas escolhem data, horário e modalidade (presencial ou online), com integração automática à Google Agenda/Meet |
| 39 | **Eventos**: nova entidade de primeiro nível (ex.: Semana de Inovação), independente do concurso, com inscrição pública e perfil auto-aprovado |
| 40 | Divulgação de Evento na home pública, cadastro dedicado e login com Google no fluxo de inscrição |
| 41 | **Aplicativo web instalável** para quem participa de um Evento — mesmo link no celular e no computador, instalação sempre opcional |
| 42 | **Crachá digital de credenciamento** por inscrição, com QR e código de 6 caracteres, pronto para impressão |
| 43 | **Leitura de código pelo próprio participante** — câmera (quando o navegador suportar) ou digitação manual, nunca pela equipe organizadora |
| 44 | **Confirmação de inscrição também dentro do aplicativo**, além do e-mail já existente |
| 45 | **Aviso em massa aos inscritos de um Evento**, por e-mail e pelo aplicativo, enviado em lotes para não sobrecarregar o e-mail institucional |
| 46 | **Agenda de atividades do Evento** (cursos, palestras, seminários), com inscrição pelo próprio participante, vagas e lista de espera opcionais por atividade |
| 47 | **Confirmação de presença por atividade**, com código fixo próprio para o espaço físico, lido sempre pelo próprio participante |
| 48 | **Atividades também em modalidade online e híbrida**, com código próprio de confirmação de presença online; **exportação de inscritos no formato exigido por sistemas parceiros**; cadastro de **Facilitadores** (instrutor, professor, palestrante) por atividade; **acesso próprio ao aplicativo de Evento**, separado do painel do Concurso; **temas de cor personalizáveis**, escolhidos por cada usuário |
| 49 | **Trabalhos**: submissão e avaliação de artigos e resumos expandidos de um Evento, com motor próprio (sem relação com o Concurso), avaliação às cegas por avaliadores avulsos, cálculo de resultado com desempate configurável |

---

<div align="center">

**Núcleo de Projetos e Inovação — Tribunal de Justiça do Estado de Roraima**

</div>
