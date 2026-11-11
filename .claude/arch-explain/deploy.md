# Deploy (produção na Oracle)

## O que é

Como um push na `master` vira o app no ar, numa VM ARM "Always Free" da Oracle só dele: o CI
passa, as imagens de produção vão para o GHCR e o GitHub Actions entra na VM por `ssh` para subir
a versão nova. O Caddy do próprio app atende as portas 80/443 e cuida do HTTPS.

## Analogia

**Marmitas congeladas.** Dev é a cozinha de testes, com tudo solto na bancada. A imagem de
produção é a marmita lacrada com o número do lote (o SHA do commit). O GHCR é o frigorífico. O
Actions é o caminhão que confere, leva e troca a vitrine. A VM é uma loja de rua, com a porta
(o Caddy) dando direto para a calçada. Rollback = pôr de volta o lote anterior.

## Como funciona aqui

```
push na master → deploy.yml
  ├─ testes   → ci.yml (Pint, Larastan, Pest)
  ├─ imagens  → runner ARM, build alvo prod, push: ghcr.io/dancarvalhodev/ceu-de-hoje-{php,caddy}:<sha>
  └─ publicar → scp do compose.prod.yaml; ssh deploy@VM: pull, migrate --force, up -d --wait, prune

internet → caddy (80/443, HTTPS automático) → php-fpm
```

- **VM:** criada com `infra/cloud-init.yaml` (Docker, usuário `deploy`, portas 80/443 no
  firewall, pasta `/srv/ceu-de-hoje`). Roteiro em `docs/deploy.md`.
- **Projeto Compose** `-p ceu-prod` em `/srv/ceu-de-hoje`, com `caddy`, `php`, `queue`,
  `scheduler`, `mysql` e `redis`. Só o `caddy` publica portas.
- **Um Caddyfile para dev e produção:** o endereço do site vem de `SITE_ADDRESS`. Em dev é `:80`
  (HTTP puro); em produção é o domínio, e com um nome de domínio o Caddy pede e renova o
  certificado na Let's Encrypt sozinho. O `http://127.0.0.1` extra é para o healthcheck, que
  chama `/up` (rota de saúde do Laravel) e assim testa o PHP junto.
- **Subida de cada container PHP:** `php artisan optimize` e depois o processo (fpm, `queue:work`
  ou `schedule:work`). Cada container tem o próprio sistema de arquivos, então cada um gera o seu
  cache.
- **Dev × produção** (Dockerfile multi-stage, alvos `dev` e `prod`):

  | | Dev | Produção |
  |---|---|---|
  | Código | volume | dentro da imagem |
  | Composer | com dev | `--no-dev --optimize-autoloader` |
  | Assets | `just vite` | estágio Node; `public/build` nas duas imagens (o `@vite` lê o manifest no PHP) |
  | Xdebug / debug | sim | não |
  | HTTPS | não | Caddy, automático |

- **`.env` do servidor:** `/srv/ceu-de-hoje/.env`, criado à mão. Leva `APP_KEY`, `APP_URL`
  `https://`, senhas do MySQL e `RESEND_API_KEY`.

### Laravel em produção

- `APP_ENV=production`, `APP_DEBUG=false`, `LOG_CHANNEL=stderr` (log no `docker compose logs`).
- **`migrate --force`**: o `--force` responde ao "tem certeza?" que o Laravel faz em produção.
- **`optimize`**: config, rotas e views em cache ([docs](https://laravel.com/docs/13.x/deployment#optimization)).
- **HTTPS sem `trustProxies`**: quem termina o TLS é o Caddy que fala FastCGI com o PHP, e o
  `php_fastcgi` já manda `HTTPS=on`; o Laravel enxerga `https` sem configuração. O `trustProxies`
  ([docs](https://laravel.com/docs/13.x/requests#configuring-trusted-proxies)) só é preciso com
  um proxy HTTP na frente (load balancer, Cloudflare); confiar em `*` sem proxy deixaria qualquer
  cliente forjar IP e protocolo pelos cabeçalhos `X-Forwarded-*`.
- **Links do e-mail** saem do `APP_URL`, porque o job roda na fila, sem requisição.
- **Resend**: `MAIL_MAILER=resend`; o Mailable não muda, só o carteiro.
- Cada deploy cria containers novos, então o worker já sobe com o código novo.

## Pegadinhas

- **`env()` fora de `config/` devolve `null`** com a config em cache. Use `config()`.
- **Nunca `down --volumes`:** o volume `caddy-data` guarda os certificados, e a Let's Encrypt
  limita quantos emite por semana para o mesmo domínio. O mesmo comando apagaria o MySQL.
- **DNS antes do primeiro deploy:** o Caddy pede o certificado assim que sobe. Se o domínio ainda
  não aponta para a VM, o pedido falha.
- **Trocar a `APP_KEY` desloga todo mundo.** Gere uma vez e guarde.
- **Mudou o `.env` do servidor?** Recrie os containers (`up -d --force-recreate`).
- **Rollback não desfaz migração.** A imagem volta, o banco não.
- **A primeira imagem no GHCR nasce privada**; a VM não baixa até ela ficar pública.

## Comandos úteis (na VM, como `deploy`)

```bash
cd /srv/ceu-de-hoje
docker compose -p ceu-prod -f compose.prod.yaml ps
docker compose -p ceu-prod -f compose.prod.yaml logs -f caddy
docker compose -p ceu-prod -f compose.prod.yaml exec php php artisan about
IMAGE_TAG=<sha-anterior> docker compose -p ceu-prod -f compose.prod.yaml up -d --wait   # rollback
```
