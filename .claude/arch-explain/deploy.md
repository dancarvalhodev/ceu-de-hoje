# Deploy (produção na Oracle)

## O que é

Como um push na `master` vira o app no ar, na VM ARM da Oracle que já roda o `landing-page`:
o CI passa, as imagens de produção vão para o GHCR e o GitHub Actions entra na VM por `ssh` para
subir a versão nova. Na frente fica o **edge**, o Caddy do `landing-page`, com HTTPS.

## Analogia

**Marmitas congeladas.** Dev é a cozinha de testes, com tudo solto na bancada. A imagem de
produção é a marmita lacrada com o número do lote (o SHA do commit). O GHCR é o frigorífico. O
Actions é o caminhão que confere, leva e troca a vitrine. A VM é um shopping, e o edge é a
portaria que manda cada cliente (domínio) para a loja certa. Rollback = pôr de volta o lote
anterior.

## Como funciona aqui

```
push na master → deploy.yml
  ├─ testes   → ci.yml (Pint, Larastan, Pest)
  ├─ imagens  → runner ARM, build alvo prod, push: ghcr.io/dancarvalhodev/ceu-de-hoje-{php,caddy}:<sha>
  └─ publicar → scp do compose.prod.yaml; ssh: pull, migrate --force, up -d --wait, prune
```

- **Projeto separado na VM:** `-p ceu-prod` em `/srv/ceu-de-hoje`, com `caddy`, `php`, `queue`,
  `scheduler`, `mysql` e `redis`. Só o `caddy` entra na rede `edge`, com um alias que o Caddyfile
  do edge usa (`reverse_proxy <alias>:80`).
- **Dev × produção** (Dockerfile multi-stage, alvos `dev` e `prod`):

  | | Dev | Produção |
  |---|---|---|
  | Código | volume | dentro da imagem |
  | Composer | com dev | `--no-dev --optimize-autoloader` |
  | Assets | `just vite` | estágio Node; `public/build` nas duas imagens (o `@vite` lê o manifest no PHP) |
  | Xdebug / debug | sim | não |

- **`.env` do servidor:** `/srv/ceu-de-hoje/.env`, criado à mão. Leva `APP_KEY`, `APP_URL`
  `https://`, senhas do MySQL e `RESEND_API_KEY`.

### Laravel em produção

- `APP_ENV=production`, `APP_DEBUG=false`, `LOG_CHANNEL=stderr` (log no `docker compose logs`).
- **`migrate --force`**: o `--force` responde ao "tem certeza?" que o Laravel faz em produção.
- **`optimize`**: config, rotas e views em cache ([docs](https://laravel.com/docs/13.x/deployment#optimization)).
- **`trustProxies`**: o edge fala HTTP com o app; sem isso, os links saem `http://`
  ([docs](https://laravel.com/docs/13.x/requests#configuring-trusted-proxies)).
- **Resend**: `MAIL_MAILER=resend`; o Mailable não muda, só o carteiro.
- Cada deploy cria containers novos, então o worker já sobe com o código novo.

## Pegadinhas

- **`env()` fora de `config/` devolve `null`** com a config em cache. Use `config()`.
- **Trocar a `APP_KEY` desloga todo mundo.** Gere uma vez e guarde.
- **Mudou o `.env` do servidor?** Recrie os containers (`up -d --force-recreate`).
- **Rollback não desfaz migração.** A imagem volta, o banco não.
- **A primeira imagem no GHCR nasce privada**; a VM não baixa até ela ficar pública.

## Comandos úteis (na VM)

```bash
cd /srv/ceu-de-hoje
docker compose -p ceu-prod -f compose.prod.yaml ps
docker compose -p ceu-prod -f compose.prod.yaml logs -f php
docker compose -p ceu-prod -f compose.prod.yaml exec php php artisan about
IMAGE_TAG=<sha-anterior> docker compose -p ceu-prod -f compose.prod.yaml up -d --wait   # rollback
```
