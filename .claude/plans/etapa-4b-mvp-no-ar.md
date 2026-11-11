# Plano: etapa 4b do cronograma — MVP no ar

Plano conferido e aprovado pelo dev em 2026-11-06.

## Objetivo

Publicar o app numa VM ARM "Always Free" da Oracle só dele, no modelo de deploy do `landing-page`:
imagens ARM no GHCR e deploy por `ssh` + `docker compose` a cada push na `master`. O Caddy do
próprio app atende as portas 80/443, com HTTPS automático (sem o "edge" do `landing-page`;
revisado em 2026-11-11, ver decisão 12). O alerta das 20h passa a sair por e-mail de verdade,
pelo Resend.

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
- **VM** (`infra/README.md` e `infra/cloud-init.yaml` do `landing-page`): Ubuntu 24.04 em
  `VM.Standard.A1.Flex`, cloud-init que instala o Docker, cria o usuário `deploy` (só chave SSH,
  grupo `docker`), libera 80/443 no iptables e cria a pasta em `/srv`. As portas também são
  abertas na Security List da Oracle.
- **Edge** (`infra/edge/` do `landing-page`): Caddy com as portas 80/443 na frente de vários
  sites. **Não usado aqui** desde a decisão 12: com a VM só para o app, o Caddy do app faz esse
  papel.
- **Rollback:** rodar de novo o `up` com o `IMAGE_TAG` de um commit anterior.

## O que muda por ser Laravel

- **Assets do Vite:** o `@vite` do Blade lê `public/build/manifest.json` **no container `php`**, e
  o Caddy serve os arquivos. As duas imagens precisam do `public/build`, gerado num estágio Node.
- **Migrações:** `php artisan migrate --force` a cada deploy (o `--force` é exigido em produção).
- **Caches de produção:** `php artisan optimize` (config, rotas, views e eventos em cache) ao subir
  o container. Pegadinha: com a config em cache, `env()` fora de `config/` devolve `null`.
- **HTTPS:** o Caddy do app termina o TLS e fala FastCGI com o PHP mandando `HTTPS=on`; o
  Laravel enxerga `https` sem `trustProxies`. Links do e-mail vêm do `APP_URL`.
- **Mais serviços:** `queue`, `scheduler`, `mysql` e `redis` com volumes próprios, sem portas no
  host, no projeto Compose `-p ceu-prod`, em `/srv/ceu-de-hoje`.
- **Segredos no servidor:** `/srv/ceu-de-hoje/.env` criado à mão (`APP_KEY`, senhas do banco,
  `RESEND_API_KEY`), nunca no repositório nem no CI.
- **Logs:** `LOG_CHANNEL=stderr` em produção, para o `docker compose logs` mostrar tudo.

## Decisões do dev (já tomadas)

1. **Oracle Cloud**, mesmo modelo de deploy do `landing-page`. ~~Na VM dele~~: VM própria desde a
   decisão 12.
2. **Projeto Compose** `-p ceu-prod` em `/srv/ceu-de-hoje`, MySQL e Redis próprios, sem portas no
   host.
3. ~~Bloco novo no Caddyfile do edge~~ (substituída pela decisão 12).
4. **E-mail com Resend** (driver nativo do Laravel), verificando o domínio.
5. **Antes do deploy:** budget alert de US$ 1 na Oracle (conta pay-as-you-go).
6. ~~Alias na rede `edge`~~ (2026-11-10, etapa 4; removido pela decisão 12).
7. **Domínio e remetente:** `ceudehoje.danielcarvalho.dev`, e-mails de
   `alerta@ceudehoje.danielcarvalho.dev` (decidido em 2026-11-10, etapa 5).
8. ~~`trusted_proxies` no Caddyfile do app e `trustProxies` no Laravel~~ (2026-11-10, etapa 5;
   removidos pela decisão 12: sem proxy na frente, confiar em `*` deixaria forjar
   `X-Forwarded-*`).
9. **Migrações automáticas** (2026-11-10, etapa 6). Desde 2026-11-11 rodam na subida do
   container `php` (`migrate --force` antes do `optimize`), não mais num `run` separado do
   `deploy.yml`: o deploy vira um `up` só e uma falha aparece em `logs php`.
10. **Imagens públicas no GHCR**, sem `docker login` na VM (2026-11-10, etapa 6).
11. **CI sem duplicar na `master`:** o `ci.yml` ignora push na `master`; lá os testes rodam só
    dentro do `deploy.yml` (fora do plano original, 2026-11-10, etapa 6).
12. **VM "Always Free" só para o app, sem edge** (2026-11-11, durante a etapa 6): o compartilhamento
    com o `landing-page` complicava sem ganho. O `caddy` do app publica 80/443 e faz o HTTPS
    (`SITE_ADDRESS` no Caddyfile, volumes `caddy-data`/`caddy-config`); a VM nasce de
    `infra/cloud-init.yaml` (cópia do `landing-page`, sem a rede `edge`); o Actions entra como
    `deploy@`. Cabe no Always Free junto com a VM do `landing-page` (limite de 4 OCPUs e 24 GB
    somando as duas).
13. **Cadastro aberto em produção** (2026-11-11, etapa 7): qualquer pessoa pode se registrar e
    receber e-mails. É um portfólio e o volume será baixo; o limite é o plano grátis do Resend
    (100 e-mails por dia, 3.000 por mês).

## Decidir com o dev

Nada em aberto: domínio, alias, migrações, imagens públicas e cadastro viraram as decisões 6, 7,
9, 10 e 13.

## Regras de sempre

- MVP: só o que está listado. Nada de Kubernetes, Ansible, painel ou monitoramento.
- Nada de segredo no repositório. Variáveis novas vão para o `.env.example`, sem valores reais.
- O que acontece fora deste repositório (Oracle, DNS, Resend, secrets do GitHub) é feito pelo
  dev, seguindo o roteiro da etapa 7.

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
6. **Workflow de deploy e VM própria.** `ci.yml` ganha `workflow_call`. `deploy.yml` novo, no
   modelo do `landing-page`: CI → imagens ARM no GHCR → `scp` do `compose.prod.yaml` → `pull`,
   migrações (decisão 9) e `up -d --no-build --wait` com `-p ceu-prod` → `prune`, tudo como
   `deploy@`. Ajustes da decisão 12: `caddy` com portas 80/443 e HTTPS pelo `SITE_ADDRESS`, sem
   rede `edge`; sem `trusted_proxies`/`trustProxies` (o `TrustProxiesTest` passa a garantir que
   `X-Forwarded-*` forjados são ignorados);
   `infra/cloud-init.yaml`. Conferência: `docker compose -f compose.prod.yaml config -q`, leitura
   do YAML e `just up` (dev igual); o primeiro push de verdade fica para a etapa 7.
7. **Roteiro do servidor e primeiro deploy.** `docs/deploy.md`, no formato do `infra/README.md`
   do `landing-page` (tabela de passos, um comando por vez, "Se algo não funcionar"). **Do zero,
   sem reaproveitar nada da VM ou da rede do `landing-page`** (pedido do dev em 2026-11-11):
   - conta Oracle (ou a existente), Pay As You Go opcional, budget alert de US$ 1 e os limites do
     Always Free;
   - chaves SSH (a pessoal para `ubuntu`, uma de deploy nova, `~/.ssh/ceu-deploy`);
   - VCN própria pelo assistente e portas 80/443 (TCP) e 443 (UDP) na Security List;
   - criar a VM ARM com o `infra/cloud-init.yaml` (troca da chave de deploy, "Out of capacity");
     conferir a VM, com o plano B à mão se o cloud-init não for aplicado;
   - **subdomínio e Resend já prontos** (dev, 2026-11-11): só editar o registro A `ceudehoje` na
     Squarespace para o IP novo, sem mexer nos registros do Resend, e conferir com `dig`
     **antes** do deploy; a chave do Resend só vai para o `.env`;
   - `/srv/ceu-de-hoje/.env` com `APP_KEY` nova;
   - secrets do repositório (`SSH_HOST` = só o IP, `SSH_KEY`, `SSH_KNOWN_HOSTS`);
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
- [x] 4. `compose.prod.yaml` — confirmada em 2026-11-10
- [x] 5. Laravel em produção — confirmada em 2026-11-10
- [x] 6. Workflow de deploy e VM própria — confirmada em 2026-11-11
- [x] 7. Roteiro do servidor e primeiro deploy — confirmada em 2026-11-11 (app no ar em
  https://ceudehoje.danielcarvalho.dev)

Plano concluído em 2026-11-11.
