# Plano: etapa 2 do cronograma — Base do projeto

## Objetivo

Criar o Céu de Hoje com infraestrutura Docker própria (no modelo do Wecker, sem Sail), login
próprio, testes, CI e o cadastro do local de observação de cada usuário, protegido por Policy. Sem
starter kit e sem Vue.

Pronto quando: `just install` sobe o projeto do zero; um usuário se registra, entra, cadastra o
próprio local e não consegue ver nem alterar o local de outro; o CI roda Pest, Pint e Larastan
verde no GitHub.

## Ponto de partida

Pasta `ceu-de-hoje` com este kit (`.claude/`, `docs/`) e um repositório git vazio. **O Mac não tem
PHP nem Composer para este projeto:** tudo roda em containers, inclusive a criação do projeto.

## Referência: o Wecker

A infraestrutura segue a do Wecker (https://github.com/dancarvalhodev/wecker), adaptada para
Laravel 13. Peça ao dev o caminho do clone local do Wecker; se não houver, clone em uma pasta
temporária fora do projeto. Leia, antes das etapas 2 e 3:

- `docker-compose.yml`, `justfile`, `env-example`
- `bin/php/Dockerfile`, `bin/php/fpm-pool.conf`, `bin/php/entrypoint.sh`
- `config/caddy/Caddyfile`, `config/php/php.ini`
- `.claude/arch-explain/infra.md` e `.claude/arch-explain/just.md`

Mantenha o que vale (Ubuntu 24.04 + PPA do Ondřej, PHP 8.5, Composer copiado da imagem `composer:2`,
php-fpm em TCP 9000 com logs na saída, Caddy na frente, receitas `just` com checagens `_docker`,
`_env` e `_running`, sem `restart: always`, bancos sem porta publicada). Tire o que é só do Wecker
(acesso ao `docker.sock` e o `entrypoint.sh` que existe para ele, Postgres, sessão no `php.ini`,
scripts `bin/migrate.php` e `bin/admin-create.php`).

## Fatos já conferidos sobre o skeleton do Laravel 13

- Exige PHP `^8.3`; o PHP 8.5 do Wecker serve.
- Vem com **PHPUnit**, não Pest.
- O `.env.example` vem com SQLite e com sessão, cache e fila no driver `database`.
- O `phpunit.xml` já usa SQLite em memória nos testes.
- **Traz um `CLAUDE.md` e um `AGENTS.md` na raiz** que mandam instalar PHP no host (php.new) e o
  Laravel Boost. Isso contradiz este projeto: esses dois arquivos não podem ficar no repositório.
- O Vite vem com Tailwind 4.

## Decisões do dev (2026-11-01)

1. **Vários locais por usuário (`hasMany`).** Etapa 3 do cronograma precisará de seletor de local;
   o alerta da etapa 4 usará a nota do melhor local (um e-mail por usuário).
2. **Testes em SQLite em memória**, como o skeleton já vem. CI sem serviço de banco.

## Ajustes aprovados (2026-11-01)

Prevalecem sobre o texto das etapas abaixo:

- **Etapa 1:** `create-project` com `--no-scripts`; mover também os dotfiles do skeleton.
- **Etapas 2 a 4:** `queue`, `scheduler` e a receita `restart-workers` ficam para a etapa 4 do
  cronograma, quando existir o primeiro Job.
- **Etapa 8:** `$this->withoutVite()` no `TestCase` base; sem Node no CI.
- **Etapas 10 e 11:** incluir `destroy`; a Policy tem `update` e `delete`, sem `view`, já que não
  há `show`.
- **`.idea/` não é versionado.**
- **Etapa 2:** o Caddy monta só `./public` (como no Wecker), porque não há upload no escopo. Se
  surgir, montar o projeto inteiro para o link `public/storage` funcionar.

## Etapas de implementação

1. **Criar o projeto Laravel 13 sem PHP no host.** Rodar o `composer create-project laravel/laravel`
   num container descartável da imagem `composer:2`, com a pasta do projeto montada, gerando numa
   subpasta temporária (o `create-project` exige pasta vazia e esta já tem o kit). Mover o conteúdo
   para a raiz **sem** o `CLAUDE.md` e o `AGENTS.md` do skeleton e sem sobrescrever `.claude/` e
   `docs/`. Listar para o dev o `! rm` da subpasta temporária, do `CLAUDE.md`, do `AGENTS.md` e do
   `database/database.sqlite`, se tiverem sido criados. No relatório: versão do Laravel instalada.
2. **Imagem e compose.** Na pasta `docker/` (não `bin/`, que no Laravel não existe e confunde):
   `docker/php/Dockerfile` adaptado do Wecker com as extensões que o Laravel 13 e o projeto usam
   (bcmath, curl, intl, mbstring, mysql, redis, sqlite3 para os testes, xml, zip, xdebug),
   `fpm-pool.conf`, `php.ini` de desenvolvimento (Xdebug em `trigger` com
   `client_host = host.docker.internal`, que é o certo no Docker Desktop do Mac) e
   `docker/caddy/Caddyfile` para o `public/` do Laravel. `compose.yaml` na raiz com os serviços
   `caddy`, `php`, `queue` (`queue:work`), `scheduler` (`schedule:work`), `mysql` (8.4),
   `redis`, `mailpit` (porta 8025) e `node` (só para rodar npm e o Vite, fora do `up` padrão).
   Healthcheck no MySQL e no Redis, e `depends_on` com `service_healthy`, para não precisar do
   `_wait-db` do Wecker. Volumes nomeados para os dados. O Caddy monta o projeto inteiro só para
   leitura, para o link `public/storage` funcionar. **Um `.env` só**: o Compose lê as variáveis
   `DB_*` do mesmo `.env` do Laravel, em vez dos dois arquivos do Wecker. Não subir nada ainda.
3. **`.env.example` e `justfile`.** Ajustar o `.env.example` para MySQL no host `mysql`, sessão,
   cache e fila no Redis, e-mail SMTP no Mailpit, `APP_URL=http://localhost`,
   `APP_LOCALE=pt_BR`, `APP_FAKER_LOCALE=pt_BR` e `APP_PORT=80`. `justfile` no padrão do Wecker com
   receitas: `install` (setup + up + composer install + key:generate só se faltar + migrate + npm
   install + build), `setup`, `up`, `stop`, `status`, `logs`, `shell`, `artisan`, `migrate`,
   `fresh` (com confirmação), `tinker`, `composer`, `test`, `stan`, `cs`, `cs-fix`, `npm`, `vite`,
   `restart-workers` e `reset` (com confirmação). Validar com `docker compose config -q` e
   `just --list`. O dev roda `just install` e confere http://localhost e http://localhost:8025.
4. **Ambiente conferido.** Vite em container: ajustar o `vite.config.js` (`host` `0.0.0.0`, porta
   fixa 5173, HMR em `localhost`) e conferir que `just vite` atualiza a página sem recarregar.
   Conferir que `queue` e `scheduler` ficam de pé. Instalar o Pest pela documentação dele para
   Laravel, converter os testes de exemplo e deixar `just test` e `just cs` passando. Criar
   `.claude/arch-explain/infra.md` e `just.md` no formato dos do Wecker.
5. **Layout e página inicial.** Componente Blade `<x-layout>` com título "Céu de Hoje" e menu que
   mostra "Entrar / Criar conta" para visitante e "Sair" para logado (`@auth` / `@guest`). A rota
   `/` vira uma página inicial simples em português.
6. **Registro.** Rotas `register` (GET e POST) no grupo `guest`, controller, view com nome, e-mail,
   senha e confirmação, validação com mensagens em português, `Auth::login()` e redirecionamento
   para a lista de locais (rota provisória até a etapa 10).
7. **Login e logout.** Rotas `login` (GET e POST) no grupo `guest` e `logout` (POST) no grupo
   `auth`. `Auth::attempt()`, `session()->regenerate()` no login, `invalidate()` e
   `regenerateToken()` no logout. Mensagem de erro em português sem revelar se o e-mail existe.
   Testes com Pest: registro cria usuário e loga; login com senha certa entra e com senha errada
   não; logout sai; visitante é barrado de rota `auth` e logado é barrado de rota `guest`.
8. **CI no GitHub Actions + Larastan.** Instalar o Larastan (`larastan/larastan`, nível 5, com
   `phpstan.neon`) e a receita `just stan` passando. Workflow em `.github/workflows/ci.yml`
   rodando, em passos separados, Pint, Larastan e Pest a cada push e pull request. O CI não usa o
   compose: PHP 8.5 direto no runner, com as mesmas extensões da imagem.
9. **Model `Location`.** `make:model Location -mf`: migration com `user_id` (foreign key com
   `cascadeOnDelete`), `name`, `latitude` e `longitude` (`decimal`, precisão a definir no
   relatório), `timezone`; `$fillable`, casts e relação com `User` (conforme a decisão 1);
   factory com coordenadas e fusos válidos.
10. **Cadastro do local.** `LocationController` de recurso (só `index`, `create`, `store`, `edit`,
    `update`), rotas no grupo `auth`, views Blade e `StoreLocationRequest` /
    `UpdateLocationRequest`: nome obrigatório, latitude entre -90 e 90, longitude entre -180 e 180,
    fuso válido (regra `timezone`), mensagens em português. O `user_id` vem do usuário logado,
    nunca do formulário.
11. **Policy e testes.** `make:policy LocationPolicy --model=Location`: `view` e `update` só para o
    dono, aplicada com `Gate::authorize()`; o `index` lista só os locais do usuário logado; local
    de outro usuário devolve 403. Testes: cadastro válido salva com o `user_id` certo; dados
    inválidos voltam com erro em cada campo; usuário não vê nem edita local de outro; visitante é
    mandado para o login.

Ao fim de cada etapa, o dev faz commit e ao fim da etapa 11, o PR. Depois, marcar as tarefas da etapa 2 no cronograma.

## Fora deste plano

A versão de **produção** da imagem (sem Xdebug, código copiado para dentro da imagem, OPcache
ajustado) fica para o plano da etapa 4, junto com o deploy na Oracle. A base é a mesma desta.

## Relação com o cronograma

| Sessão do cronograma | Etapas deste plano |
|---|---|
| Criar o projeto e subir para o GitHub | 1 a 4 (o push é do dev) |
| Registro, login e logout | 5 a 7 |
| Instalar o Pest e escrever o teste do login | 4 e 7 |
| GitHub Actions e Model Local | 8 e 9 |
| Tela de cadastro com Form Request | 10 |
| Policy e testes | 11 |

## Progresso

- [x] 1. Criar o projeto Laravel 13 sem PHP no host — confirmada em 2026-11-02
- [x] 2. Imagem e compose — confirmada em 2026-11-02
- [x] 3. `.env.example` e `justfile` — confirmada em 2026-11-02
- [x] 4. Ambiente conferido — confirmada em 2026-11-04
- [x] 5. Layout e página inicial — confirmada em 2026-11-04
- [x] 6. Registro — confirmada em 2026-11-04
- [x] 7. Login e logout — confirmada em 2026-11-04
- [x] 8. CI no GitHub Actions + Larastan — confirmada em 2026-11-04
- [x] 9. Model `Location` — confirmada em 2026-11-04
- [x] 10. Cadastro do local — confirmada em 2026-11-04
- [ ] 11. Policy e testes — entregue, aguardando conferência
