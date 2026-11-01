# Passagem de sessão

Resumo para continuar o desenvolvimento em outro computador (ou voltar a este). Quem terminar uma
sessão atualiza este arquivo; quem começar lê este arquivo primeiro.

Última atualização: 2026-11-01.

## Onde paramos

- **Etapa 1 do cronograma concluída:** o dev fez o curso Getting Started with Laravel (Chirper).
- **O projeto Laravel ainda não existe.** A pasta tem só este kit. O Mac não tem PHP nem Composer
  para este projeto; a criação do projeto e a infraestrutura Docker (modelo do Wecker, sem Sail)
  são as primeiras etapas do plano.
- **Próximo:** executar `.claude/plans/etapa-2-base-do-projeto.md` com a skill `modo-codando`.

## Decisões que valem

- **Sem starter kit.** Autenticação feita à mão, como no curso.
- **Vue e Inertia só na etapa 5.** Até lá, Blade.
- **Código em inglês, interface em português** (tabela de nomes em `docs/PROJETO.md`).
- **Infra própria, sem Sail:** Caddy + php-fpm (PHP 8.5, Ubuntu 24.04) + MySQL + Redis + Mailpit
  no Compose, comandos pelo `justfile`, no modelo do Wecker. Nada de PHP, Composer ou npm no host.
- **Um `.env` só**, lido pelo Laravel e pelo Compose.
- **Cada mudança deixa limpos** (Pint, Larastan e testes) os arquivos que tocou.

## Como o dev quer trabalhar

- **Uma etapa por vez:** entregar, dizer como conferir, perguntar "está tudo correto?" e só seguir
  depois de um sim claro. Um comentário ou um "commitei" não é aprovação. Para um plano com etapas,
  usar a skill `modo-codando`.
- **Ele está aprendendo Laravel e Vue:** explicar o jeito do framework, comparar com PHP puro e
  Laminas, nomear a mágica e linkar a documentação. Não explicar PHP básico e não gerar enormes textos
  explicativos.
- **MVP sempre:** o mínimo de arquivos e classes. Questionar o que não vale o custo, com
  recomendação; a decisão é do dev.
- **Clareza acima de esperteza** (`.claude/rules/code-style.md`): código para júnior e pleno, poucos
  comentários (só o porquê; regex sempre com um comentário breve).
- **Explicações com analogias**, curtas. **Documentação e relatórios enxutos.**
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

- **Mac M1 (ARM):** use imagens com versão ARM; se alguma não tiver, avise antes de trocar.
- **Xdebug no Docker Desktop:** `client_host = host.docker.internal`, não o `172.17.0.1` do Wecker.
- **Sem `restart: always`:** depois de reiniciar o Mac, rode `just up`.
- **Depois de mudar o `.env`:** `just artisan config:clear`. Depois de mudar um Job:
  `just restart-workers`.
