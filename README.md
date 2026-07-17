# Sistema de Gestão da Semana de Inovação e do Prêmio de Inovação — TJRR

PHP 7.3 puro (sem framework, sem Composer packages além do autoload e do
PHPMailer), MySQL 8. Em produção o sistema fica em `https://npi.tjrr.jus.br/si`,
subpasta do mesmo `DocumentRoot` do site institucional em Joomla — por isso
não existe uma pasta `public/` isolada, e toda a árvore do projeto é
web-acessível (ver seção "Segurança" abaixo).

O sistema nasceu na Fase 1 como fundação/modelo de dados e, ao longo de 18
fases, virou um **motor genérico configurável** para gerir qualquer edição do
Prêmio de Inovação — cadastro e homologação de equipes, formulários
dinâmicos, avaliação por critérios/fórmula/desempate, resultados públicos, e
(Fase 18) um painel completo de conteúdo institucional que substitui a antiga
página estática por edição.

## Conceitos centrais

A hierarquia de dados é sempre a mesma, para qualquer edição futura (6º, 7º
Prêmio...):

```
Concurso (uma edição, ex. "5º Prêmio de Inovação")
└── Trilha (segmento de público, ex. Interna/Externa)
    ├── Tema (do edital) → Desafio (pergunta que a equipe escolhe)
    └── Etapa (fase do processo, ordenada)
        ├── Formulário Dinâmico (campos configuráveis, sem código)
        │   └── Submissão (resposta de uma equipe, dados_json)
        ├── Critérios de Avaliação + Fórmula de Pontuação + Regras de Desempate
        └── Resultado da Etapa / Resultado da Trilha (ranking, publicação)
```

Perfis: `administrador` (acesso total), `suporte` (leitura + algumas ações,
restrito), `avaliador` (designado por categoria/critério, escopado por
concurso), `participante` (autoatendido, vinculado a uma equipe). Todo
cadastro nasce `pendente` e só loga depois de aprovado por um Administrador
— login manual ou via Google, mesma regra.

Desde a Fase 18, a home pública (`home/index`) e o painel de conteúdo
institucional (slideshow, banners, blocos de texto rico, prêmios, FAQ,
documentos/editais, biblioteca de mídia, cronograma com eventos avulsos,
contato) são inteiramente configuráveis pelo Admin, por concurso — nada
fica "hardcoded" no código, incluindo o histórico de edições anteriores
(`edicoes/index`).

## Subir o ambiente local

```bash
docker compose up -d --build
```

Sobe dois containers:

- `app`: PHP 7.3 + Apache, porta `8090` (http://localhost:8090)
- `db`: MySQL 8.0.33, porta `3311`

Na primeira vez, copie o arquivo de credenciais de exemplo:

```bash
cp config/local.example.php config/local.php
```

Os valores padrão já batem com o `docker-compose.yml` (banco `npi_si_dev`,
usuário `npi_si`). Para o servidor real do TJRR, ajuste `config/local.php`
com as credenciais de `npi_db3` (nunca commitar esse arquivo — já está no
`.gitignore`).

A seção `google` de `config/local.php` precisa do Client ID/Secret reais do
Google Cloud Console (projeto `sgsi`) para o login Google funcionar de fato —
sem eles, o clique em "Entrar com sua conta Google" redireciona normalmente,
mas a troca do código por token falha.

## Rodar as migrations

```bash
docker compose exec app php database/migrate.php
```

Aplica todas as migrations em `database/migrations/` (numeradas, aplicadas em
ordem). É idempotente — pode rodar de novo sem erro, só aplica o que ainda
não foi aplicado (controle na tabela `migracoes_executadas`).

## Criar o primeiro Administrador

```bash
docker compose exec app php database/seed_admin.php
```

Pede nome, e-mail e senha via prompt interativo e cria o usuário já com
`status = aprovado` e papel `administrador` global — é o único jeito de criar
um Administrador, pois todo outro cadastro (autoatendido, feito pelo próprio
interessado em `/index.php?r=cadastro/index`) nasce `pendente` e só pode
logar depois de aprovado por um Administrador já existente.

## Fluxo de cadastro e aprovação

1. Qualquer pessoa se cadastra em `auth/cadastro` (nome, e-mail, senha).
2. A conta nasce com `status = pendente` — login é recusado até aprovação.
3. Um Administrador acessa `usuarios/index`, vê a lista de pendentes e
   aprova ou rejeita.
4. Só depois de aprovado o usuário consegue logar em `auth/login`.

## Login Google

Fluxo OAuth2 "Authorization Code" implementado via `curl` puro (sem pacote
Composer), em `app/Core/GoogleOAuth.php`. Mesma regra de aprovação do
cadastro manual: **toda conta nova (via Google ou manual) nasce `pendente`**.

- Vínculo de conta por **e-mail exato**: se o e-mail do Google bater com um
  usuário manual já existente cujo `google_id` ainda seja nulo, vincula
  automaticamente; se não bater com ninguém, cria um usuário novo pendente.
- `state` aleatório gravado na sessão antes do redirect (proteção CSRF),
  validado e descartado (uso único) no retorno.
- Nenhum `access_token`/`refresh_token` é armazenado.
- `email_verified=false` do Google é rejeitado.

**Redirect URIs cadastradas no Google Cloud Console:**
- Produção: `https://npi.tjrr.jus.br/si/index.php?r=auth/googleCallback`
- Dev local (Docker): `http://localhost:8090/index.php?r=auth/googleCallback`

## Segurança (por que não há pasta `public/`)

Como o Joomla já é dono do `DocumentRoot` do domínio inteiro e o sistema é só
uma subpasta (`/si`), não há como isolar `app/`, `config/`, `database/` atrás
de um `DocumentRoot` próprio nem de um `.htaccess` (`AllowOverride None` em
produção). Mitigações adotadas:

- **Guarda de boot**: todo arquivo PHP fora de `index.php` começa com
  `if (!defined('SI_BOOT')) { http_response_code(403); exit('Acesso negado'); }`.
  `index.php` é o único arquivo que define essa constante antes do `require`.
- **Credenciais nunca em texto puro**: `config/local.php` retorna um array
  PHP (sempre executado pelo servidor), nunca um `.env` de texto.
- **Scripts de CLI recusam rodar via web**: todo script em `database/` checa
  `php_sapi_name() !== 'cli'` e retorna 403 se for requisitado por HTTP.
- **Uploads de imagem/arquivo** validam o mime real do arquivo (`finfo`,
  nunca confiam no `Content-Type` enviado pelo navegador) e protegem contra
  path traversal (`realpath()` + checagem de prefixo) antes de gravar em
  disco — ver `ImagemService`/`ArquivoService`.

## Módulos do sistema (por área)

O roteamento é via query string (`index.php?r=modulo/acao/parametro`), sem
depender de `.htaccess`. Cada módulo abaixo é um controller registrado em
`app/Core/Router.php`; o prefixo de produção (`/si`) fica centralizado em
`config/config.php` (`base_path`) e no helper `url()`.

| Área | Módulos (rota) |
|---|---|
| Autenticação/cadastro | `auth`, `cadastro`, `usuarios`, `meuPerfil`, `sessao` |
| Hierarquia do concurso | `concursos`, `trilhas`, `temas` (Tema/Desafio), `etapas` |
| Formulários dinâmicos | `formularios`, `campos`, `submissao`, `inscricao` |
| Homologação de equipes | `homologacao`, `participante` |
| Avaliação | `criterios`, `formulas`, `desempate`, `designacoes`, `categoriasAvaliador`, `vagasAvaliador`, `avaliacao` |
| Resultados | `resultados` (admin), `resultadosPublicos`, `apuracao` |
| **Home pública/conteúdo (Fase 18)** | `slides`, `banners`, `blocos`, `premios`, `contatosConcurso`, `faq` (banco global), `faqConcurso` (ativação por edição), `documentos`, `midia` (biblioteca global), `eventosCronograma`, `editorMidia` (upload do editor rico), `edicoes` (Edições Anteriores, público) |
| Identidade visual/conteúdo legado | `tema` (cores/logo/favicon, global ou por concurso), `conteudo` (chave-valor antigo, mantido só para não perder dados — ver nota abaixo) |
| Administração geral | `auditoria`, `configuracoes`, `notificacoesPainel`, `navegacao` (árvore lateral, endpoint JSON) |
| Público (sem login) | `home`, `edicoes`, `resultadosPublicos`, `submissao`/`inscricao` (formulário de submissão/inscrição), `politica.php`/`termos.php` (fora do roteador) |

> **Nota sobre `conteudo`/`ConteudoAdminController`**: até a Fase 17, o
> conteúdo da home (hero, "Sobre", "Premiação", contato) vivia numa tabela
> chave-valor plana (`conteudos_site`, sem `concurso_id`). A Fase 18
> substituiu isso por entidades de verdade escopadas por concurso (Slides,
> Blocos de Conteúdo, Prêmios, Contato). A tabela e o controller antigos
> **não foram apagados** (sem `DROP`, para não perder dado histórico), só
> saíram do menu — use `database/migrar_conteudo_home.php` para portar o
> conteúdo antigo para as tabelas novas.

## Estrutura

```
si/
├── index.php                 # front controller único
├── politica.php               # Política de Privacidade (pública, fora do roteador)
├── termos.php                 # Termos de Serviço (pública, fora do roteador)
├── app/
│   ├── Core/                  # Router, Database (PDO), Auth, View, Controller, GoogleOAuth, Texto, Mailer
│   ├── Controllers/           # ~44 controllers (admin + público)
│   ├── Middleware/            # RoleMiddleware (perfil + concurso)
│   ├── Repositories/          # 1 classe por entidade, sempre via PDO preparado + Auditoria::registrar()
│   ├── Services/               # regras de negócio (avaliação, upload de imagem/arquivo, e-mail, navegação)
│   ├── Validation/             # CpfValidador, YoutubeValidador, UploadPdfValidador
│   └── Views/
│       ├── admin/              # telas do painel (uma pasta por entidade)
│       ├── home/                # home pública (index.php + parciais _secao.php)
│       └── publico/             # páginas públicas fora do layout admin (resultado, edições anteriores)
├── assets/
│   ├── css/site.css            # CSS único do projeto (público + admin), sem framework
│   ├── js/                     # JS vanilla, um arquivo por funcionalidade, sem lib externa
│   └── uploads/                # imagens/arquivos enviados via admin (gitignored)
├── config/                     # config.php, database.php, google.php, smtp.php, local.php (gitignored)
├── database/
│   ├── migrate.php
│   ├── migrations/              # 076 migrations numeradas, aplicadas em ordem
│   └── *.php                    # scripts de manutenção via CLI — ver seção abaixo
├── storage/                     # uploads/submissoes/ (privado, fora de assets/), logs/, sessions/
└── docker-compose.yml
```

## Scripts de manutenção (`database/*.php`)

Todos seguem o mesmo contrato: **só rodam via CLI** (bloqueados por HTTP),
**por padrão são dry-run** (mostram o que fariam, sem gravar nada) e só
gravam de verdade com a flag `--confirmar`. Rode sempre primeiro sem
`--confirmar`, confira a saída, e só então repita com a flag.

Em dev: `docker compose exec app php database/<script>.php ...`
Em produção (sem Docker, deploy via tarball+rsync, sem git no servidor):
`php database/<script>.php ...`, direto na pasta do sistema no servidor.

### Setup/infraestrutura

- **`migrate.php`** — aplica todas as migrations pendentes. Sem flags, sem
  dry-run (é sempre seguro/idempotente por natureza).
  `php database/migrate.php`

- **`seed_admin.php`** — cria o primeiro Administrador (prompt interativo:
  nome, e-mail, senha). Único jeito de criar um Administrador.
  `php database/seed_admin.php`

- **`seed_formularios_inscricao.php`** — cria os 2 formulários de Inscrição
  de Equipe (Trilha Externa/Interna) espelhando os campos reais dos editais
  vigentes. Idempotente (pula a trilha se já existir formulário com o mesmo
  nome).
  `php database/seed_formularios_inscricao.php`

### Equipes e integrantes

- **`gerenciar_membro_equipe.php`** — adiciona, remove ou substitui um
  integrante de qualquer equipe (genérico, não hardcoda equipe/pessoa
  nenhuma). Identifica a equipe por `--equipe-id=` ou `--equipe-nome=`; se o
  nome não bater exatamente, sugere equipes com nome parecido.
  ```
  # Adicionar
  php database/gerenciar_membro_equipe.php --equipe-id=129 --acao=adicionar \
    --nome="Fulano da Silva" [--cpf=... --email=... --telefone=... --vinculo=... --papel=integrante] [--homologar] --confirmar

  # Remover
  php database/gerenciar_membro_equipe.php --equipe-nome="Nome da Equipe" --acao=remover \
    --participante-nome="Fulano da Silva" --confirmar

  # Substituir (mantém o papel de quem saiu)
  php database/gerenciar_membro_equipe.php --equipe-id=129 --acao=substituir \
    --participante-nome="Fulano da Silva" --nome="Novo Nome" [--cpf=... --email=...] [--homologar] --confirmar
  ```
  `--homologar` marca o vínculo novo como já homologado (pula a fila de
  homologação do admin) — use só quando o integrante já foi validado por
  outro meio. Sem a flag, o vínculo nasce `pendente`, como qualquer inscrição
  normal.

- **`renomear_equipe.php`** — troca só o `nome_equipe`, preservando vínculo
  institucional e observações. Avisa (sem bloquear) se já existir outra
  equipe com o mesmo nome na mesma trilha.
  `php database/renomear_equipe.php --equipe-id=129 --novo-nome="Nome Novo" --confirmar`

- **`migrar_equipe_trilha.php`** — migra uma equipe de uma trilha para
  outra. Zera o `desafio_id` (o desafio escolhido pertence ao Tema/trilha
  antigos e não existe na trilha nova).
  `php database/migrar_equipe_trilha.php (--id=123 | --nome="Nome da Equipe") --trilha-destino="Trilha Externa" --confirmar`

- **`importar_submissoes_google_forms.php`** — importa/reimporta respostas
  de um Google Forms externo (usado enquanto a plataforma não tinha
  formulário próprio) para dentro de `submissoes.dados_json`, casando por
  nome da equipe (com fallback por e-mail do respondente). Upsert: reimportar
  atualiza a submissão existente, não duplica. Lê CSVs fixos em
  `database/dados_importacao/`.
  `php database/importar_submissoes_google_forms.php --confirmar`

### Usuários e acesso

- **`excluir_usuario.php`** — exclui uma conta de usuário por completo (não
  existe essa opção na interface, de propósito). Não apaga a
  inscrição/participante em si, só o vínculo de login. Recusa excluir se o
  usuário já tiver notas lançadas, a menos que rode com `--forcar-com-notas`
  além de `--confirmar`.
  ```
  php database/excluir_usuario.php (--id=42 | --email=... | --nome="...") --confirmar
  ```

- **`liberar_acesso_auditoria.php`** — gera um link de definição de senha
  para uma conta **já ativa** (uso excepcional, ex.: pedido de auditoria/
  processo judicial). Não desativa o login existente. O link é só impresso
  no terminal, não enviado por e-mail automaticamente — exige `--dominio`
  explícito (não há `HTTP_HOST` em CLI).
  `php database/liberar_acesso_auditoria.php --email=fulano@exemplo.com --dominio=https://npi.tjrr.jus.br --confirmar`

- **`liberar_acesso_teste.php`** *(setup de ambiente de teste)* — vincula um
  usuário já cadastrado manualmente ao participante de mesmo e-mail, quando
  esse vínculo não foi criado pelo fluxo normal de homologação.
  `php database/liberar_acesso_teste.php --email=fulano@exemplo.com --confirmar`

- **`definir_cpf_teste.php`** / **`definir_email_teste.php`** *(setup de
  ambiente de teste)* — forçam CPF/e-mail de um participante para um valor
  de teste. Não existe tela nenhuma que altere isso, de propósito.
  ```
  php database/definir_cpf_teste.php (--participante-id=42 | --nome="...") --cpf=00000000000 --confirmar
  php database/definir_email_teste.php (--participante-id=42 | --nome="...") --email=fulano@teste.com --confirmar
  ```

- **`testar_login_google.php`** *(setup de ambiente de teste)* — testa a
  lógica de vínculo/criação de conta via Google sem depender de um login
  real.
  `php database/testar_login_google.php <google_id> <email> <nome> <email_verified 0|1>`

### Conteúdo público (Fase 18)

- **`migrar_conteudo_home.php`** — porta o conteúdo hoje em `conteudos_site`
  (hero, "Sobre", "Premiação", contato) para as tabelas novas escopadas por
  concurso (Slides, Blocos de Conteúdo, Contato), para o concurso **ativo**
  no momento em que roda. Idempotente: não duplica nem sobrescreve o que já
  foi editado manualmente após a primeira migração. Não mexe no logo (feature
  nova, tela de Identidade Visual).
  `php database/migrar_conteudo_home.php --confirmar`

### Auditoria/backup

- **`exportar_dump_completo.php`** — exporta um dump SQL completo (estrutura
  + dados) via PDO puro (produção não tem `mysqldump` instalado). **Contém
  dado pessoal sensível** (CPF, e-mail, telefone) — trate a saída como
  confidencial, apague a cópia local assim que entregue.
  `php database/exportar_dump_completo.php --confirmar`

## Deploy em produção

Produção não tem acesso a `git` (operador sem esse acesso) — deploys de
atualização usam tarball + `rsync`, documentado a partir da Fase 14 em
`Implantar.md`/`DeployFase*.md` (fora deste repositório, na raiz do projeto
`/home/f3011432/Code/NPI/`). Acesso SSH: `ssh tjadmin@172.16.1.80 -p 1409`.

## Histórico de fases (resumo)

| Fase | Entrega |
|---|---|
| 1 | Fundação: modelo de dados, autenticação, login Google |
| 2 | Concurso/Trilha/Etapa/Formulários Dinâmicos |
| 3 | Importação via CLI, tela Suporte, CMS leve, identidade visual |
| 4 | Notificação por e-mail, editor de fórmula livre, dados reais 2026 |
| 5–6 | Refinamento visual, motor de avaliação, fluxo real de inscrição/homologação |
| 7–8 | Navegação em árvore do admin, tela de Usuários |
| 9 | Fórmula ponderada automática, conteúdo da submissão visível ao avaliador |
| 10 | Categorias de avaliador, sorteio automático de designação |
| 11 | Redesenho da tela do avaliador |
| 12 | Notificações do painel, trava de classificação entre etapas |
| 13 | Importação do Google Forms, página pública de resultados |
| 14 | **Deploy em produção** (13/07/2026), auditoria, Configurações, Meu Perfil |
| 15–16 | Correções pós-deploy, gestão de convites |
| 17 | 9 bugs + 3 melhorias, retificação de dados reais, correção de vazamento de CSV |
| 18 | **Painel de conteúdo institucional completo**: home pública dinâmica por edição (slideshow, banners, blocos de texto rico, prêmios, FAQ, documentos/editais versionados, biblioteca de mídia, cronograma com eventos avulsos, contato), repositório público de Edições Anteriores, editor de texto rico e reordenação por arrastar-e-soltar 100% vanilla (sem dependência de terceiros) |
