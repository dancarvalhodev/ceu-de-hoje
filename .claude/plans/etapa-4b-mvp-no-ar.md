# Plano: etapa 4b do cronograma — MVP no ar

Plano conferido e aprovado pelo dev em 2026-11-06.

## Objetivo

Publicar o app na VM ARM da Oracle que já roda o `landing-page`, no mesmo modelo dele: imagens ARM
no GHCR, deploy por `ssh` + `docker compose` a cada push na `master`, e o Caddy "edge" (que já
existe na VM) encaminhando o subdomínio para o app. O alerta das 20h passa a sair por e-mail de
verdade, pelo Resend.

Pronto quando: um push na `master` passa no CI, gera as imagens, publica na VM e roda as
migrações; o app abre em HTTPS no subdomínio; o worker e o scheduler estão de pé; um e-mail de
teste chega numa caixa real; Pint, Larastan e Pest verdes.

## Ponto de partida

- Plano 4a concluído: `CheckSkyJob` agendado às 20h, `GoodSkyAlert`, serviços `queue` e
  `scheduler` no `compose.yaml` de dev.
- Imagem `php` só de dev (`docker/php/Dockerfile`, com Xdebug; o código entra por volume). O
  Caddy de dev usa a imagem oficial e lê `public/` por volume.
- CI (`.github/workflows/ci.yml`): Pint, Larastan e Pest em todo push e PR. Sem deploy.

## O modelo do `landing-page` (lido no GitHub)

- **`deploy.yml`** (push na `master`): reaproveita o CI (`workflow_call`); job `imagens` em
  `ubuntu-24.04-arm` (ARM nativo, sem emulação) faz `docker compose -f docker-compose.prod.yml
  build` e `push` para o GHCR com o `GITHUB_TOKEN`, tag = SHA do commit (`IMAGE_TAG`); job
  `publicar` grava a chave SSH, faz `scp` do compose de produção para `/srv/landing-page/` e roda
  `IMAGE_TAG=... docker compose -p site-prod -f ... up -d --no-build --wait`, depois um
  `docker image prune` de imagens com mais de 7 dias. Secrets: `SSH_HOST`, `SSH_KEY`,
  `SSH_KNOWN_HOSTS`.
- **Compose de produção:** `caddy` e `php` com `image: ghcr.io/dancarvalhodev/...:${IMAGE_TAG}` e
  `build` com `target: prod`; só o `caddy` entra na rede externa `edge`, com um alias; sem
  `ports`; `restart: unless-stopped`; rotação de log.
- **Dockerfiles multi-stage** com alvos `dev` e `prod`. O `prod` copia o código para dentro da
  imagem e roda `composer install --no-dev`. O Caddy de `prod` leva o próprio Caddyfile e o
  `public/`.
- **Edge** (`infra/edge/` do `landing-page`): Caddy com as portas 80/443, HTTPS automático, rede
  `edge`, `reverse_proxy site-prod:80` para o domínio. Atualizado à mão no servidor, fora do CI.
- **Rollback:** rodar de novo o `up` com o `IMAGE_TAG` de um commit anterior.

## O que muda por ser Laravel

- **Assets do Vite:** o `@vite` do Blade lê `public/build/manifest.json` **no container `php`**, e
  o Caddy serve os arquivos. As duas imagens precisam do `public/build`, gerado num estágio Node.
- **Migrações:** `php artisan migrate --force` a cada deploy (o `--force` é exigido em produção).
- **Caches de produção:** `php artisan optimize` (config, rotas, views e eventos em cache) ao subir
  o container. Pegadinha: com a config em cache, `env()` fora de `config/` devolve `null`.
- **HTTPS atrás de proxy:** o edge termina o TLS e fala HTTP com o app. Sem confiar no proxy
  (`trustProxies` em `bootstrap/app.php`), o Laravel gera links `http://`, inclusive o do e-mail.
- **Mais serviços:** `queue`, `scheduler`, `mysql` e `redis` com volumes próprios, sem portas no
  host, num projeto Compose separado (`-p ceu-prod`, `/srv/ceu-de-hoje`).
- **Segredos no servidor:** `/srv/ceu-de-hoje/.env` criado à mão (`APP_KEY`, senhas do banco,
  `RESEND_API_KEY`), nunca no repositório nem no CI.
- **Logs:** `LOG_CHANNEL=stderr` em produção, para o `docker compose logs` mostrar tudo.

## Decisões do dev (já tomadas)

1. **Oracle Cloud**, na VM ARM do `landing-page`, mesmo modelo de deploy.
2. **Projeto Compose separado** (`-p ceu-prod`, `/srv/ceu-de-hoje`), MySQL e Redis próprios, sem
   portas no host.
3. **Bloco novo no Caddyfile do edge** para o subdomínio `ceudehoje.`.
4. **E-mail com Resend** (driver nativo do Laravel), verificando o domínio.
5. **Antes do deploy:** budget alert de US$ 1 na Oracle (conta pay-as-you-go).

## Decidir com o dev

Perguntar antes da etapa que depende de cada uma.

1. **Domínio completo** (`ceudehoje.<qual domínio>`) e **remetente** do e-mail (ex.:
   `alerta@ceudehoje.<domínio>`). Etapas 5 e 7.
2. **Alias na rede `edge`.** Recomendação: um nome fixo (`ceu-de-hoje`) em vez de
   `${COMPOSE_PROJECT_NAME}`, o que dispensa a dúvida sobre de onde vem essa variável no
   `landing-page` (o alias `site-prod` depende dela). Etapa 4.
3. **Migrações automáticas no deploy.** Recomendação: sim, como um passo do `deploy.yml`
   (`docker compose run --rm php php artisan migrate --force`) antes do `up`. A alternativa é
   rodar à mão a cada deploy. Etapa 6.
4. **Imagens públicas no GHCR**, como no `landing-page`. Recomendação: sim; elas não levam
   segredos (o `.env` fica no servidor). A alternativa é um `docker login` no servidor com um token
   de leitura. Etapa 6.
5. **Cadastro aberto em produção.** Qualquer pessoa pode se registrar e receber e-mails (no plano
   grátis do Resend: 100 por dia, 3.000 por mês). Recomendação: deixar aberto; é um portfólio e o
   volume será baixo. Etapa 7.

## Regras de sempre

- MVP: só o que está listado. Nada de Kubernetes, Ansible, painel ou monitoramento.
- Nada de segredo no repositório. Variáveis novas vão para o `.env.example`, sem valores reais.
- O que acontece fora deste repositório (Oracle, DNS, Resend, Caddyfile do edge, secrets do
  GitHub) é feito pelo dev, seguindo o roteiro da etapa 7.

## Etapas de implementação

1. **Estudar e documentar o deploy.** Criar `.claude/arch-explain/deploy.md`: o caminho de um push
   até a VM (CI → imagens ARM no GHCR → `ssh` + `compose up`), imagens multi-stage `dev`/`prod`,
   o que muda no Laravel em produção (`optimize`, `migrate --force`, `APP_KEY`, `trustProxies`,
   logs em `stderr`), a rede `edge` e o rollback por `IMAGE_TAG`. Sem código de aplicação.
2. **Imagem `php` de produção.** `docker/php/Dockerfile` com estágios `base`, `dev` (Xdebug, como
   hoje) e `prod`; um estágio `assets` (Node) roda o `npm run build`. O `prod` copia o código e o
   `public/build`, roda `composer install --no-dev --optimize-autoloader` e usa um `php.ini` de
   produção. Criar `.dockerignore`. O `compose.yaml` de dev passa a usar `target: dev`.
   Conferência: o dev roda `docker build --target prod docker/php` (ou pelo compose de produção)
   e `just up`; dev continua igual.
3. **Imagem `caddy` de produção.** `docker/caddy/Dockerfile` com o Caddyfile e o `public/` (com o
   `public/build`) dentro da imagem. O Caddyfile de produção é o de dev, com o `root` apontando para
   a pasta copiada. Conferência: build local da imagem.
4. **`compose.prod.yaml`.** Serviços `caddy`, `php`, `queue`, `scheduler`, `mysql` e `redis`;
   imagens `ghcr.io/dancarvalhodev/ceu-de-hoje-{php,caddy}:${IMAGE_TAG}`; `env_file: .env`;
   `restart: unless-stopped`; healthchecks; rotação de log; só o `caddy` na rede `edge`, com
   alias (decisão 2). O `php` roda `php artisan optimize` antes do fpm. Conferência:
   `docker compose -f compose.prod.yaml config -q` e, opcionalmente, subir localmente com uma rede
   `edge` criada à mão.
5. **Laravel em produção.** `trustProxies` em `bootstrap/app.php`; driver do Resend
   (`composer require resend/resend-php`, `MAIL_MAILER=resend`, `RESEND_API_KEY`); variáveis de
   produção no `.env.example`, comentadas. Teste: uma requisição com `X-Forwarded-Proto: https`
   gera links `https://`.
6. **Workflow de deploy.** `ci.yml` ganha `workflow_call`. `deploy.yml` novo, no modelo do
   `landing-page`: CI → imagens ARM no GHCR → `scp` do `compose.prod.yaml` → `pull`, migrações
   (decisão 3) e `up -d --no-build --wait` com `-p ceu-prod` → `prune`. Conferência: `actionlint`
   ou leitura do YAML; o primeiro push de verdade fica para a etapa 7.
7. **Roteiro do servidor e primeiro deploy.** `docs/deploy.md` com o passo a passo para o dev:
   - budget alert de US$ 1 na Oracle;
   - registro DNS do subdomínio;
   - domínio e chave no Resend;
   - `/srv/ceu-de-hoje/.env` com `APP_KEY` nova;
   - bloco no Caddyfile do edge (repositório `landing-page`) e reinício do edge;
   - secrets do repositório;
   - primeiro push e imagens públicas no GHCR;
   - conferência final e rollback.

   O dev executa; o Claude acompanha e corrige o que falhar. Conferência: o app abre em HTTPS, o
   cadastro funciona e um `CheckSkyJob` disparado à mão manda e-mail para uma caixa real.

Ao fim de cada etapa, o dev faz commit.

## Fora deste plano

- Backup do MySQL, monitoramento, alertas de erro e CDN.
- Ambiente de homologação.
- Recuperação de senha e verificação de e-mail (versão 2, `docs/PROJETO.md`).

## Progresso

- [x] 1. Estudar e documentar o deploy — confirmada em 2026-11-09
- [x] 2. Imagem `php` de produção — confirmada em 2026-11-09
- [x] 3. Imagem `caddy` de produção — confirmada em 2026-11-10
- [ ] 4. `compose.prod.yaml`
- [ ] 5. Laravel em produção
- [ ] 6. Workflow de deploy
- [ ] 7. Roteiro do servidor e primeiro deploy
