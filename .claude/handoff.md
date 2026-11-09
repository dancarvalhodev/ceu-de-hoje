# Passagem de sessão

Resumo para continuar o desenvolvimento em outro computador (ou voltar a este). Quem terminar uma
sessão atualiza este arquivo; quem começar lê este arquivo primeiro.

Última atualização: 2026-11-09.

## Onde paramos

- **Plano da etapa 2 (`.claude/plans/etapa-2-base-do-projeto.md`) concluído: as 11 etapas
  confirmadas em 2026-11-04.** Falta, com o dev: PR para a `main` e marcar as tarefas da etapa 2
  no cronograma.
- **Plano da etapa 3 (`.claude/plans/etapa-3-pontuacao.md`) concluído: as 7 etapas confirmadas
  em 2026-11-05.** Falta, com o dev: PR para a `main` e ajustar o cronograma com os nomes novos
  (tabela em `docs/PROJETO.md`).
- **Plano `.claude/plans/melhorias-local.md`** (botão "Usar minha localização") concluído em
  2026-11-05; testado pelo dev no navegador.
- **Etapa 4 dividida em dois planos.** 4a (alerta, local): `.claude/plans/etapa-4a-alerta.md`,
  **concluído: as 5 etapas confirmadas em 2026-11-06** (e-mail conferido no Mailpit). Falta, com
  o dev: PR para a `main` e marcar as tarefas no cronograma. 4b (MVP no ar):
  `.claude/plans/etapa-4b-mvp-no-ar.md`, conferido e aprovado em 2026-11-06; etapa 1 confirmada
  em 2026-11-09; etapa 2 entregue, aguardando conferência; tem 5 pontos em "Decidir com o dev", perguntados antes da etapa que depende de cada um.
- **Ideias em aberto (não decididas):** `Http::preventStrayRequests()` para todos os testes em
  `tests/Pest.php`; `LOG_CHANNEL=null` no `phpunit.xml` para os testes não gravarem em
  `storage/logs`.

## Decisões que valem

- **Sem starter kit.** Autenticação feita à mão, como no curso.
- **React e Inertia só na etapa 5.** Até lá, Blade.
  Depois: componentes funcionais com hooks, páginas em `resources/js/Pages`, hooks próprios em
  `resources/js/hooks` com prefixo `use`.
- **Código em inglês, interface em português** (tabela de nomes em `docs/PROJETO.md`).
- **Infra própria, sem Sail:** Caddy + php-fpm (PHP 8.5, Ubuntu 24.04) + MySQL + Redis + Mailpit
  no Compose, comandos pelo `justfile`, no modelo do Wecker. Nada de PHP, Composer ou npm no host.
- **Um `.env` só**, lido pelo Laravel e pelo Compose.
- **Vários locais por usuário**, fuso padrão `America/Sao_Paulo` no cadastro.
- **Pontuação de observabilidade** (`SkyScore`, 0 a 10) com a cobertura de nuvens do momento da
  consulta (`current=cloud_cover` do Open-Meteo) e a iluminação da lua (pacote
  `solaris/php-moon-phase`). Tela **"Céu agora"** (rota `sky`, `SkyController`).
- **Alerta (etapa 4a):** `CheckSkyJob` uma vez por dia às 20h (São Paulo); `GoodSkyAlert` para quem
  tem pontuação 7 ou mais no melhor local; um e-mail por usuário.
- **Deploy (etapa 4b):** Oracle Cloud, na VM ARM do `landing-page`, no mesmo modelo (imagens ARM
  no GHCR, `ssh` + `docker compose`, Caddy "edge" na rede `edge`). Projeto Compose separado
  (`-p ceu-prod`, `/srv/ceu-de-hoje`) com MySQL e Redis próprios, sem portas no host; bloco novo
  no Caddyfile do edge para o subdomínio `ceudehoje.`. E-mail com **Resend** (driver nativo),
  verificando o domínio. Antes do deploy: budget alert de US$ 1 na Oracle (conta pay-as-you-go) e
  conferir no `landing-page` de onde vem o `COMPOSE_PROJECT_NAME` usado no alias `site-prod`.
- **Larastan nível 5** sobre `app/`, `routes/` e `database/` (testes Pest ficam de fora).
- **`.md` do repositório podem ser editados sem perguntar** (regra no `CLAUDE.md`).
- **Cada mudança deixa limpos** (Pint, Larastan e testes) os arquivos que tocou.

## Como o dev quer trabalhar

- **Uma etapa por vez:** entregar, dizer como conferir, perguntar "está tudo correto?" e só seguir
  depois de um sim claro. Um comentário ou um "commitei" não é aprovação. Para um plano com etapas,
  usar a skill `modo-codando`.
- **Ele está aprendendo Laravel e React:** explicar o jeito do framework, comparar com PHP puro e
  Laminas, nomear a mágica e linkar a documentação. Não explicar PHP básico e não gerar enormes textos
  explicativos.
- **MVP sempre:** o mínimo de arquivos e classes. Questionar o que não vale o custo, com
  recomendação; a decisão é do dev.
- **Clareza acima de esperteza** (`.claude/rules/code-style.md`): código para júnior e pleno, poucos
  comentários (só o porquê; regex sempre com um comentário breve).
- **Explicações com analogias**, curtas. **Documentação e relatórios enxutos.**
- **Resumo pós-execução direto e pequeno:** cabe numa tela, sem repetir o diff; detalhe só se ele
  perguntar (formato na skill `modo-codando`, seção 5).
- **Testes que provam o que importa** (o comportamento real), nada de teste que só confirma o óbvio.
- **CodeGraph primeiro** para entender código, quando houver `.codegraph/`. **Editar com o Edit,
  não com `sed`.**
- **Comandos simples:** um por chamada, sem laços nem `;`/`||` encadeando casos.
- **Não criar dados reais no banco de dev** sem avisar; o que exige digitação, o dev testa.
- **Git é do dev:** o Claude nunca roda `git add/commit/push/stash` nem abre PR. Quando pedido, só
  escreve a mensagem de commit: em inglês, título + poucos tópicos, com emoji compatível, terminando
  com `Co-Authored-By: <modelo atual> <noreply@anthropic.com>`. Antes, confere o `git status` e
  avisa se o staging estiver incompleto.
- **Apagar arquivos é com o dev:** o Claude lista o `! rm ...`.
- **Não ler nem editar o `.env`** sem permissão explícita.

## Pegadinhas do ambiente

- **O dev também trabalha no Fedora (Linux, SELinux ativo).** Os containers `php` e `node` rodam
  com o uid do host (`HOST_UID`/`HOST_GID` exportados pelo `justfile`); sem isso, o php-fpm não
  grava em `storage/`. Detalhes em `arch-explain/infra.md`.
- **Mac M1 (ARM):** use imagens com versão ARM; se alguma não tiver, avise antes de trocar.
- **Xdebug no Docker Desktop:** `client_host = host.docker.internal`, não o `172.17.0.1` do Wecker.
- **Sem `restart: always`:** depois de reiniciar o Mac, rode `just up`.
- **Depois de mudar o `.env`:** `just artisan config:clear`. Depois de mudar um Job:
  `just restart-workers`.
