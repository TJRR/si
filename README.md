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
| Conexões no evento | Leitura do crachá de outro participante pelo aplicativo: as duas pessoas ficam conectadas e pontuam, e cada uma decide quais contatos compartilha |
| Divulgação do evento | O participante comprova que publicou sobre o evento nas próprias redes sociais, ou que passou a acompanhar os canais do órgão, e recebe pontos na hora; a organização confere depois, por amostragem |
| Bônus do evento | Conquistas que o sistema apura sozinho a partir das presenças confirmadas, como participar de cinco atividades diferentes ou estar presente em todos os dias; a organização cadastra quais valem, quanto valem e o que cada uma exige |
| Pesquisa de satisfação | Questionário próprio do evento, com perguntas cadastradas pela organização; as respostas ficam guardadas separadas de quem respondeu, e responder pode valer pontos |
| Pontos por presença | Cada tipo de atividade vale os pontos que a organização definir, com um extra para quem confirma a presença antes do início; uma atividade pode ter valor próprio |
| Credenciamento no local | Código afixado na entrada do evento, lido pelo próprio participante no aplicativo para registrar a chegada |
| Competições e experiências | Karaokê, batalha de ideias e afins: quem participa lê na hora o código que o responsável mostra, e pontua pela participação, sem inscrição prévia nem vencedor |
| Classificação do evento | Soma de todas as origens de pontos em tempo real, com critérios de desempate escolhidos pela organização, "Minha pontuação" e "Regras do jogo" no aplicativo, e encerramento que congela a classificação |

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
| **Representante de estande** | Pessoa do expositor ou do patrocinador, convidada pela organização, que só acompanha o próprio estande num Evento: dados exibidos, cartaz com o código e total de visitas |

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
Prêmio de Inovação), página pública de entrada própria, separada da home do
Concurso, e inscrição pública com acesso próprio — quem ainda não tem conta entra com Google ou cria um
cadastro dedicado, ambos com liberação imediata, sem espera por aprovação
manual (adequado ao volume de um evento aberto ao público). Quem se inscreve
passa a acessar o evento por um **aplicativo web instalável** — mesmo link,
mesma experiência no celular e no computador; "instalar" (adicionar à tela
inicial/área de trabalho) é sempre um atalho opcional, nunca obrigatório.
Cada inscrição gera um **crachá digital de credenciamento**, com código
único pronto para impressão, disponível assim que a pessoa se inscreve. O
próprio aplicativo lê esse código pela câmera do celular (quando o
navegador suportar) ou por digitação manual, sempre pelo próprio
participante. A organização também pode **conferir o crachá** na entrada, por
uma tela própria que mostra nome, foto e situação da inscrição sem registrar
nada. A
confirmação de inscrição chega por e-mail e também como notificação dentro
do próprio aplicativo, com textos que a própria organização escreve. O evento tem sua própria **agenda de atividades**
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

Ao fim do evento, o sistema emite os **certificados**: o de participação no
evento, o de cada atividade e o de apresentação de trabalho. Quem tem direito
a cada um é definido pela organização, que escreve o texto do documento e
escolhe a arte de fundo pela própria interface. A carga horária é apurada pelo
sistema a partir das presenças confirmadas, descontando atividades que
aconteceram no mesmo horário, de modo que o documento nunca declare mais horas
do que o evento teve. Cada certificado sai com um código de conferência e
endereço de uma **página pública de validação**, onde qualquer pessoa confirma
que o documento é autêntico. O participante retira o dele no aplicativo, e a
organização pode emitir e imprimir em lote, inclusive num arquivo único com um
certificado por folha. Quando o documento fica disponível, a pessoa é avisada
pelo sino e por e-mail, uma vez por certificado.

Dentro da Semana de Inovação, **Trabalhos** organiza a submissão e a
avaliação de artigos e resumos expandidos, com motor próprio (sem nenhuma
relação com o Concurso): o autor envia o texto pelo próprio aplicativo do
evento, com autor principal e coautores (que, se a organização quiser, já
saem inscritos no evento no ato do envio, com um único e-mail de confirmação);
a organização define os critérios
de nota e convida avaliadores avulsos, que analisam cada trabalho às cegas
(sem saber quem é o autor) e lançam a nota por critério; o sistema calcula
o resultado final, aplica desempate quando necessário e seleciona os
trabalhos aprovados. A organização confere a prévia e **publica o resultado**;
só então cada autor (principal ou coautor) vê, no próprio aplicativo, a situação
final do trabalho e, conforme a configuração do evento, a nota final, a posição
na classificação e a média por critério, e é avisado por e-mail e pelo sino do
aplicativo.
Depois do evento, a organização publica os **Anais**: o volume único, em PDF, com os
trabalhos apresentados. O volume pode ser montado pela equipe fora do sistema ou
**pelo próprio sistema**: o autor principal de cada trabalho envia a versão final pelo
aplicativo, dentro do prazo definido pela organização, e o sistema junta capa, páginas
iniciais (folha de rosto, ficha catalográfica, expediente, comissões e apresentação),
sumário por eixo temático e os trabalhos, numerando as páginas. O sistema guarda cada
versão do arquivo, publica como documento do evento e no botão "Anais" do aplicativo,
mantém a lista de quais trabalhos constam no volume e avisa os autores por e-mail e
pelo sino. O PDF montado pelo sistema traz o sumário com ligações para cada trabalho e o
painel de marcadores, e a página pública do evento pode ter uma seção própria dos Anais,
com o volume publicado e a relação dos trabalhos selecionados.

Os **estandes** de expositores e patrocinadores também têm um código fixo, impresso num
cartaz com QR: o participante registra a visita pelo aplicativo, uma vez por estande, e
acumula os pontos definidos pela organização. Cada estande pode ter um representante,
convidado pela organização, que atualiza nome, descrição e logotipo, imprime o cartaz e
acompanha quantas visitas o estande recebeu, sem identificar quem visitou. A lista de
estandes aparece no aplicativo e, se a organização quiser, na página pública do evento.

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

Mais de cinquenta fases, a maioria já publicada em produção durante uma
edição real do prêmio; o bloco de Eventos/Trabalhos (Fases 39 em diante) foi
desenvolvido e testado localmente ao longo do caminho, com o primeiro deploy
de tudo esse bloco de uma vez, junto com a Fase 51.

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
| 50 | **Página pública própria para cada Evento**, separada da home do Concurso, com Cabeçalho, Quadros de apresentação, Faixas e Blocos de conteúdo exclusivos, e controle de quando cada uma fica publicada |
| 51 | **Página do evento montada por seções**: ordem, liga e desliga e menu definidos pela organização, com componentes novos (contagem regressiva, cronograma, cartões, destaques, programação por dia, perguntas frequentes, local com mapa), fontes escolhidas por evento e **documentos do evento** (edital, anexos, retificações, com versões); **declarações de aceite configuráveis** na submissão de trabalhos, com formulário que aponta o campo com problema e **inscrição automática dos autores** (opcional, por evento); **importação de trabalhos recebidos por outro canal**, com convite de acesso aos autores pela fila de envio; **qual regra decidiu cada empate** no resultado; **divulgação pública do resultado final da trilha**; pastas na biblioteca de mídia |
| 52 | **Resultado de Trabalhos publicado ao autor**: a organização confere a prévia e publica; só então o autor vê situação, seleção para apresentação, nota final, posição e média por critério (cada bloco liga e desliga por evento), com aviso por e-mail e pelo sino do aplicativo, e a possibilidade de reabrir para corrigir; **tela amigável "Este evento não existe"** para página de evento inexistente ou não publicada, com **prévia** da página não publicada para administradores |
| 53 | **Anais do Evento**: o volume único em PDF, montado pela equipe, é enviado em versões numeradas, publicado como documento do evento e como botão no aplicativo do participante, com a lista de quais trabalhos aprovados constam nele e aviso aos autores por e-mail e pelo sino |
| 54 | **Estandes** de expositores e patrocinadores, com código fixo em cartaz, visita registrada pelo participante no aplicativo com pontos e representante do estande convidado pela organização; **geração automática do volume dos Anais**, com envio da versão final de cada trabalho pelo autor, dados editoriais, comissões, sumário e numeração das páginas; na submissão de trabalhos, **um trabalho por pessoa** conferido também pelo e-mail e pela conta, e cadastro seguido direto do formulário para quem ainda não tem conta |
| 55 | **Conexões entre participantes**: uma pessoa lê o crachá da outra no aplicativo e as duas ficam conectadas e pontuam, com pontos e limite definidos por evento; cada uma escolhe o que mostra à outra (foto, cargo, órgão, minicurrículo, telefone com WhatsApp e redes sociais), e **"Meu Perfil" passa a existir dentro do aplicativo**, com dados, contatos, escolha de tema e troca de senha |
| 56 | **Divulgação em redes sociais**: o participante comprova pelo aplicativo que publicou sobre o evento, ou que passou a acompanhar um canal do órgão, e recebe os pontos no ato; valor, prova aceita e limites são definidos por rede social e por evento, e a organização confere depois, podendo anular a pontuação com justificativa que chega ao participante |
| 57 | **Bônus automáticos e pesquisa de satisfação**: a organização cadastra os bônus do evento, com nome livre, forma de apurar, exigência e pontos, e o sistema os concede sozinho a partir das presenças já confirmadas, mostrando ao participante quanto falta para cada um; a pesquisa de satisfação tem perguntas configuráveis em quatro formatos e guarda as respostas separadas de quem respondeu, de modo que nem a organização consegue ligar uma coisa à outra; o **formulário de submissão de trabalhos** passa a poder ser visto e preenchido antes de entrar no sistema, com a entrada pedida só na hora de enviar, e o que foi preenchido, arquivos inclusive, volta ao formulário depois da entrada |
| 58 | **Gamificação do evento**: pontos por presença definidos por tipo de atividade, com extra de pontualidade; credenciamento no local lido pelo próprio participante; competições e experiências em que se pontua por participar; e a **classificação geral**, que soma em tempo real as seis origens de pontos, aplica os critérios de desempate escolhidos pela organização e é congelada no encerramento definido por ela. No aplicativo, "Minha pontuação" e "Regras do jogo" montadas a partir do cadastro de cada módulo |
| 59 | **Certificados do evento**: certificado de participação, de cada atividade e de apresentação de trabalho, com texto e arte definidos pela organização, carga horária apurada pelo sistema descontando atividades no mesmo horário, régua de quem tem direito configurável, retirada pelo próprio participante no aplicativo, emissão e impressão em lote pela organização, e **código de conferência com página pública de validação** |
| 60 | **Pendências do sistema**: textos dos e-mails do evento editáveis pela organização, com palavras-chave; seção dos Anais na página pública, com a relação dos trabalhos selecionados; sumário do PDF dos Anais com ligações e marcadores; aviso de certificado disponível; conferência de crachá pela organização, sem registrar nada; operação em lote na Biblioteca de mídia; casas decimais configuráveis nas notas de Trabalhos; expurgo automático das imagens da Divulgação por prazo; e mensagens do sistema padronizadas pela causa |
| 61 | **Remetente dos e-mails definido pela organização**: o nome que aparece como remetente de todos os e-mails passa a ser o mesmo da assinatura, em Configurações, Contato; avisos de segurança e redefinição de senha com texto que serve a qualquer concurso ou evento |

---

<div align="center">

**Núcleo de Projetos e Inovação — Tribunal de Justiça do Estado de Roraima**

</div>
