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

## Rodar as migrations

```bash
docker compose exec app php database/migrate.php
```

Cria as 19 tabelas (schema central: usuarios, perfis, concursos, trilhas,
etapas, formularios dinamicos, submissoes, criterios, formulas de pontuacao,
regras de desempate, notificacoes, log de auditoria) e semeia os 3 perfis
(administrador, suporte, avaliador). E idempotente — pode rodar de novo sem
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

## Rotas (Fase 1)

Roteamento via query string (`index.php?r=modulo/acao/parametro`), sem
depender de `.htaccess`/`mod_rewrite`. O prefixo de producao (`/si`) fica
centralizado em `config/config.php` (`base_path`) e no helper `url()` —
nenhuma view/controller escreve `/si` na mao.

| Rota | Descricao |
|---|---|
| `auth/login` | Formulario e processamento de login |
| `auth/logout` | Encerra sessao |
| `cadastro/index` | Autocadastro (nasce pendente) |
| `usuarios/index` | Lista cadastros pendentes (admin) |
| `usuarios/aprovar` | Aprova um cadastro pendente (admin) |
| `usuarios/rejeitar` | Rejeita um cadastro pendente (admin) |
| `home/index` | Painel pos-login (exige perfil administrador) |
| `home/painel/{concursoId}` | Rota de exemplo p/ papel restrito a um concurso |

## Estrutura

Ver plano completo em `/home/f3011432/.claude/plans/ancient-twirling-globe.md`.

```
si/
├── index.php              # front controller unico
├── app/
│   ├── Core/               # Router, Database (PDO), Auth, View, Controller
│   ├── Controllers/
│   ├── Middleware/          # RoleMiddleware (perfil + concurso)
│   ├── Repositories/
│   ├── Services/
│   └── Views/
├── config/                 # config.php, database.php, local.php (gitignored)
├── database/
│   ├── migrate.php
│   ├── seed_admin.php
│   └── migrations/          # 001..019
├── storage/                 # uploads/, logs/, sessions/
└── docker-compose.yml
```

## Gancho para as proximas fases

- `usuarios.google_id` e `usuarios.status` ja suportam login Google (Fase 2)
  com o mesmo fluxo de aprovacao do cadastro manual.
- `campos_dinamicos.config_json` e `submissoes.dados_json` ja sao colunas
  JSON nativas do MySQL 8, prontas para o construtor de Formulario Dinamico
  (Fase 2).
- `formulas_pontuacao.template_codigo` ja modelado como enum de templates
  pre-definidos (Fase 4), nao editor de expressao livre.
