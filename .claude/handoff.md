# Passagem de sessão

Resumo para continuar o desenvolvimento em outro computador (ou voltar a este). Quem terminar uma
sessão atualiza este arquivo; quem começar lê este arquivo primeiro.

Última atualização: 2026-11-04.

## Onde paramos

- **Plano da etapa 2 (`.claude/plans/etapa-2-base-do-projeto.md`) concluído: as 11 etapas
  confirmadas em 2026-11-04.** Falta, com o dev: PR para a `main` e marcar as tarefas da etapa 2
  no cronograma.
- **Plano da etapa 3 escrito** em `.claude/plans/etapa-3-pontuacao.md` (7 etapas, decisões
  de fórmula, local, cache e madrugada já tomadas). **Próximo:** executar com a skill
  `modo-codando`, a partir da etapa 1.

## Decisões que valem

- **Sem starter kit.** Autenticação feita à mão, como no curso.
- **React e Inertia só na etapa 5** (trocado de Vue para React em 2026-11-04). Até lá, Blade.
  Depois: componentes funcionais com hooks, páginas em `resources/js/Pages`, hooks próprios em
  `resources/js/hooks` com prefixo `use`.
- **Código em inglês, interface em português** (tabela de nomes em `docs/PROJETO.md`).
- **Infra própria, sem Sail:** Caddy + php-fpm (PHP 8.5, Ubuntu 24.04) + MySQL + Redis + Mailpit
  no Compose, comandos pelo `justfile`, no modelo do Wecker. Nada de PHP, Composer ou npm no host.
- **Um `.env` só**, lido pelo Laravel e pelo Compose.
- **Vários locais por usuário**, fuso padrão `America/Sao_Paulo` no cadastro.
- **Nota com as nuvens de agora** (2026-11-05): o `ForecastService` usa `current=cloud_cover` do
  Open-Meteo. Saíram a janela 18h–6h, a regra da madrugada e a tabela hora a hora.
- **Nomes novos** (2026-11-05): "nota da noite" virou **pontuação de observabilidade**
  (`SkyScore`); a tela "Hoje à noite" virou **"Céu agora"** (rota `sky`, `SkyController`).
  Na etapa 4: `CheckSkyJob` e `GoodSkyAlert`. **Decidir no plano da etapa 4:** o horário do
  Job. Com as nuvens de agora, rodar às 17h avisa sobre o céu das 17h; rodar de hora em hora à
  noite (mantendo um e-mail por dia) faz mais sentido.
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
