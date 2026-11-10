# Infraestrutura (Docker Compose)

## O que é

O app roda em containers descritos no `compose.yaml` da raiz, no modelo do
[Wecker](https://github.com/dancarvalhodev/wecker) (outro projeto do dev) e sem Sail:
**caddy** (web), **php** (php-fpm com o código), **mysql**, **redis**, **mailpit** e, sob demanda,
**node**. `just up` (`docker compose up -d --build`) sobe tudo.

## Analogia

Um **restaurante**. O Caddy é o **garçom**: entrega na hora o que já está pronto no balcão
(`public/`: CSS, JS, favicon) e leva o resto à **cozinha** (php-fpm), que tem a despensa inteira
(o projeto). MySQL e Redis são o **estoque** nos fundos, sem porta para a rua. O Mailpit é uma
**caixa de correio falsa**: tudo que o app "envia" para lá, e nada sai de verdade.

## Como funciona aqui

| Serviço | Imagem | Porta no Mac | Monta | Papel |
|---|---|---|---|---|
| `caddy` | `caddy:2.11.7-alpine` | `${APP_PORT}` (80) | `public/` (leitura), `Caddyfile` | Serve estáticos e repassa o resto ao `php:9000` |
| `php` | `docker/php/Dockerfile` (Ubuntu 24.04 + PPA Ondřej, PHP 8.5) | nenhuma | projeto inteiro, `php.ini` | php-fpm, Artisan, Composer |
| `queue` | a do `php` (`extends: php`) | nenhuma | igual ao `php` | Worker da fila (`queue:work`) |
| `scheduler` | a do `php` (`extends: php`) | nenhuma | igual ao `php` | Agendamento (`schedule:work`) |
| `mysql` | `mysql:8.4` | nenhuma | volume `mysql-data` | Banco de dev |
| `redis` | `redis:8.4-alpine` | nenhuma | volume `redis-data` | Sessão, cache e fila |
| `mailpit` | `axllent/mailpit` | 8025 | — | E-mails de dev em http://localhost:8025 |
| `node` | `node:24-alpine` | 5173 | projeto inteiro | Só `just npm` e `just vite` (profile `tools`, fora do `up`) |

- **Um `.env` só.** O Laravel lê o `.env` pelo `env()` dos arquivos de `config/`; o Compose lê o
  mesmo arquivo para preencher `${DB_*}` e `${APP_PORT}`.
- **Os nomes dos serviços são os hosts.** `DB_HOST=mysql`, `REDIS_HOST=redis`,
  `MAIL_HOST=mailpit`: a rede interna do Compose resolve pelo nome.
- **`depends_on` com `service_healthy`.** O `php` só sobe quando o MySQL e o Redis respondem ao
  healthcheck, então nenhuma receita precisa esperar o banco antes de migrar.
- **Caddy → fpm.** O `php_fastcgi` manda ao fpm o caminho `/var/www/html/public/index.php`, que
  precisa existir no container `php`. Por isso os dois usam o mesmo caminho.
- **Vite em container.** O `vite.config.js` escuta em `0.0.0.0:5173` (para sair do container) e
  anuncia `localhost` ao navegador (`hmr.host`). Enquanto o `just vite` roda, existe o arquivo
  `public/hot` e o `@vite` do Blade aponta para o dev server; sem ele, usa o `public/build`.

## Pegadinhas

- **Dono dos arquivos (Linux).** O projeto é montado no container, então o uid de quem escreve
  vale no host. O `www-data` da imagem `php` é criado com o seu uid/gid (`HOST_UID`/`HOST_GID`,
  exportados pelo `justfile` a partir do `id -u`/`id -g`), e o `node` roda com o mesmo uid. Sem
  isso, o php-fpm (uid 33) não grava em `storage/` (erro 500 com `tempnam()`) e o que o Artisan ou
  o Composer geram fica com dono root. Por isso as receitas usam `docker compose exec -u www-data`.
  Um `docker compose exec php ...` à mão entra como root: evite para comandos que criam arquivos.
- **Sem `restart: always`:** depois de reiniciar o Mac, rode `just up`.
- **Senha do MySQL só vale na primeira subida.** O usuário é criado quando o volume nasce. Mudou
  `DB_*` no `.env`? `just reset` e `just install`.
- **Mudou o `.env`?** `just artisan config:clear`.
- **Mudou o `Dockerfile` ou o `fpm-pool.conf`?** `just up` reconstrói (`--build`). Mudou o
  `php.ini` ou o `Caddyfile`? `docker compose up -d --force-recreate php` (ou `caddy`).
- **Xdebug não liga sozinho:** modo `trigger`, com o cookie ou o parâmetro `XDEBUG_TRIGGER`.
  `client_host = host.docker.internal` é o certo no Docker Desktop do Mac.
- **Upload público:** o Caddy monta só `public/`. Se um dia houver `storage:link`, monte o projeto
  inteiro no Caddy, senão o link `public/storage` aponta para o vazio.
- **Mudou um Job ou o agendamento?** `just restart-workers` (detalhes em `queues-and-mail.md`).
- **Mac M1 (ARM):** só use imagens com versão ARM; se alguma não tiver, avise antes de trocar.

## Comandos úteis

```bash
just up | stop | status | logs
just shell            # sh no php
just shell mysql      # sh no mysql (mysql -u ceu_de_hoje -p)
docker compose exec php php -m       # extensões carregadas
docker compose up -d --force-recreate php
```
