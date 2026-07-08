# Sistema de Gestao da Semana de Inovacao e do Premio de Inovacao — TJRR

Fase 1 (fundacao e modelo de dados). PHP 7.3 puro (sem framework), MySQL 8, sem
Composer packages externos alem do autoload. Ambiente local roda em Docker
(container `php:7.3`) para replicar a unica versao de PHP disponivel em
producao. Em producao, o sistema fica em `https://npi.tjrr.jus.br/si`,
subpasta do mesmo `DocumentRoot` do site institucional em Joomla — por isso
nao existe uma pasta `public/` isolada e toda a arvore do projeto e
web-acessivel (ver secao "Seguranca" abaixo).

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

Os valores padrao ja batem com o `docker-compose.yml` (banco `npi_si_dev`,
usuario `npi_si`). Para o servidor real do TJRR, ajuste `config/local.php`
com as credenciais de `npi_db3` (nunca commitar esse arquivo — ja esta no
`.gitignore`).

A secao `google` de `config/local.php` precisa do Client ID/Secret reais do
Google Cloud Console (projeto `sgsi`) para o login Google funcionar de fato —
sem eles, o clique em "Entrar com sua conta Google" redireciona normalmente,
mas a troca do codigo por token falha. Ver secao "Login Google" abaixo.

## Rodar as migrations

```bash
docker compose exec app php database/migrate.php
```

Cria as 20 tabelas (schema central: usuarios, perfis, concursos, trilhas,
etapas, formularios dinamicos, submissoes, criterios, formulas de pontuacao,
regras de desempate, notificacoes, log de auditoria, submissao_cpfs) e semeia
os 3 perfis (administrador, suporte, avaliador). E idempotente — pode rodar de novo sem
erro, so aplica o que ainda nao foi aplicado (controle na tabela
`migracoes_executadas`).

## Criar o primeiro Administrador

```bash
docker compose exec app php database/seed_admin.php
```

Pede nome, e-mail e senha via prompt interativo e cria o usuario ja com
`status = aprovado` e papel `administrador` global — e o unico jeito de criar
um Administrador, pois todo outro cadastro (autoatendido, feito pelo proprio
interessado em `/index.php?r=cadastro/index`) nasce `pendente` e so pode
logar depois de aprovado por um Administrador ja existente.

## Fluxo de cadastro e aprovacao

1. Qualquer pessoa se cadastra em `auth/cadastro` (nome, e-mail, senha).
2. A conta nasce com `status = pendente` — login e recusado ate aprovacao.
3. Um Administrador acessa `usuarios/index`, ve a lista de pendentes e
   aprova ou rejeita.
4. So depois de aprovado o usuario consegue logar em `auth/login`.

Este e o mesmo padrao usado no projeto LG Conecta: cadastro sempre
autoatendido, mas com portao de aprovacao do Administrador.

## Login Google

Fluxo OAuth2 "Authorization Code" implementado via `curl` puro (sem pacote
Composer), em `app/Core/GoogleOAuth.php`. Mesma regra de aprovacao do
cadastro manual: **toda conta nova (via Google ou manual) nasce `pendente`**.

- Vinculo de conta por **e-mail exato**: se o e-mail do Google bater com um
  usuario manual ja existente cujo `google_id` ainda seja nulo, vincula
  automaticamente; se nao bater com ninguem, cria um usuario novo pendente.
  Nunca ha "merge" manual de contas por outro campo.
- `state` aleatorio gravado na sessao antes do redirect (protecao CSRF),
  validado e descartado (uso unico) no retorno.
- Nenhum `access_token`/`refresh_token` e armazenado — usado so no momento do
  login para buscar `sub`/`email`/`name` e descartado.
- `email_verified=false` do Google e rejeitado.

**Redirect URIs cadastradas no Google Cloud Console** (Client ID tipo
"Aplicativo da Web", projeto `sgsi`) — precisam bater exatamente com o que a
aplicacao envia, incluindo a query string:
- Producao: `https://npi.tjrr.jus.br/si/index.php?r=auth/googleCallback`
- Dev local (Docker): `http://localhost:8090/index.php?r=auth/googleCallback`

Testar a logica de vinculo/criacao de conta sem depender de um login real no
Google:
```bash
docker compose exec app php database/testar_login_google.php <google_id> <email> <nome> <email_verified 0|1>
```

## Seguranca (por que nao ha pasta `public/`)

Como o Joomla ja e dono do `DocumentRoot` do dominio inteiro e o novo
sistema e so uma subpasta (`/si`), nao ha como isolar `app/`, `config/`,
`database/` atras de um `DocumentRoot` proprio nem de um `.htaccess`
(confirmado no spike tecnico: `AllowOverride None`). As mitigacoes adotadas:

- **Guarda de boot**: todo arquivo PHP fora de `index.php` comeca com
  `if (!defined('SI_BOOT')) { http_response_code(403); exit('Acesso negado'); }`.
  `index.php` e o unico arquivo que define essa constante antes de dar
  `require`.
- **Credenciais nunca em texto puro**: `config/local.php` retorna um array
  PHP (sempre executado pelo servidor), nunca um `.env` de texto — um `.env`
  requisitado direto pela URL seria devolvido como texto bruto sem
  `.htaccess` para bloquear.
- **Scripts de CLI recusam rodar via web**: `database/migrate.php` e
  `database/seed_admin.php` checam `php_sapi_name() !== 'cli'` e retornam 403.

## Rotas

Roteamento via query string (`index.php?r=modulo/acao/parametro`), sem
depender de `.htaccess`/`mod_rewrite`. O prefixo de producao (`/si`) fica
centralizado em `config/config.php` (`base_path`) e no helper `url()` —
nenhuma view/controller escreve `/si` na mao.

| Rota | Descricao |
|---|---|
| `home/index` | **Landing publica** (sem login) — explica o sistema, links de login/cadastro/politica/termos |
| `home/administrativo` | Painel pos-login (exige perfil administrador) |
| `home/painel/{concursoId}` | Rota de exemplo p/ papel restrito a um concurso |
| `auth/login` | Formulario e processamento de login manual |
| `auth/google` | Inicia o login via Google (redirect para o consentimento) |
| `auth/googleCallback` | Callback do OAuth Google (cadastrada no Google Cloud Console) |
| `auth/logout` | Encerra sessao |
| `cadastro/index` | Autocadastro manual (nasce pendente) |
| `usuarios/index` | Lista cadastros pendentes (admin) |
| `usuarios/aprovar` / `usuarios/rejeitar` | Aprova/rejeita um cadastro pendente (admin) |
| `concursos/index` \| `novo` \| `editar/{id}` | CRUD de Concursos (admin) |
| `trilhas/index/{concursoId}` \| `novo/{concursoId}` \| `editar/{id}` | CRUD de Trilhas (admin) |
| `temas/index/{trilhaId}` \| `novo/{trilhaId}` \| `editar/{id}` | CRUD de Temas/Desafios (admin) |
| `etapas/index/{trilhaId}` \| `novo/{trilhaId}` \| `editar/{id}` | CRUD de Etapas, inclui vincular Formulario Dinamico (admin) |
| `formularios/index` \| `novo` \| `editar/{id}` | CRUD de Formularios Dinamicos (admin) |
| `formularios/publicar` \| `arquivar` \| `duplicar` | Transicoes de status do formulario (admin) |
| `campos/index/{formularioId}` \| `novo/{formularioId}` \| `editar/{id}` \| `mover` \| `remover` | Campos de um formulario (admin, so em rascunho) |
| `submissao/preencher/{etapaId}` | Formulario publico de submissao (sem login) |
| `submissao/enviar/{etapaId}` | Processa a submissao (validacoes + upload) |
| `submissao/sucesso/{submissaoId}` | Confirmacao de envio |

## Estrutura

Ver plano completo em `/home/f3011432/.claude/plans/ancient-twirling-globe.md`.

```
si/
├── index.php              # front controller unico
├── politica.php           # Politica de Privacidade (publica, fora do roteador)
├── termos.php             # Termos de Servico (publica, fora do roteador)
├── app/
│   ├── Core/               # Router, Database (PDO), Auth, View, Controller, GoogleOAuth, Texto
│   ├── Controllers/
│   ├── Middleware/          # RoleMiddleware (perfil + concurso)
│   ├── Repositories/
│   ├── Services/
│   ├── Validation/           # CpfValidador, YoutubeValidador, UploadPdfValidador
│   └── Views/
├── assets/js/               # JS minimo (construtor de campo, formulario publico)
├── config/                 # config.php, database.php, google.php, local.php (gitignored)
├── database/
│   ├── migrate.php
│   ├── seed_admin.php
│   ├── testar_login_google.php
│   └── migrations/          # 001..020
├── storage/                 # uploads/submissoes/, logs/, sessions/
└── docker-compose.yml
```

## Gancho para a proxima fase

- `formulas_pontuacao.template_codigo` ja modelado como enum de templates
  pre-definidos (Fase 4), nao editor de expressao livre.
- Importacao de dados do Google Forms (Fase 3) ainda nao implementada —
  `equipes`/`participantes` seguem vazias ate la.
