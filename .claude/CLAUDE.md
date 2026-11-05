# CLAUDE.md

Orientações para o Claude Code neste repositório.

## O que é

Céu de Hoje é um app Laravel 13 que diz se a noite vai ser boa para observar o céu: cada usuário
cadastra seu local, o app cruza nuvens de agora (Open-Meteo) e fase da lua numa pontuação de
observabilidade de 0 a 10,
avisa por e-mail e, mais adiante, mostra os planetas visíveis. Requisitos e escopo de cada etapa:
`docs/PROJETO.md`.

## Quem é o dev

O Dan tem cinco anos de PHP (puro e Laminas) e trabalha com PHP + MySQL + Redis em containers.
**É iniciante em Laravel e em React.** Acabou de fazer o curso oficial Getting Started with Laravel
(o app Chirper). Isso muda o jeito de trabalhar:

- **Não explique PHP, SQL, Docker ou HTTP.** Explique o **jeito Laravel**: convenções, o que o
  framework faz sozinho e onde isso está configurado.
- **Compare com o que ele conhece.** "No Laminas você registraria isso numa factory; aqui o
  container resolve pelo tipo do construtor."
- **Nomeie a mágica.** Quando aparecer facade, route model binding, mass assignment, convenção de
  nome de tabela ou um `make:*` que gera vários arquivos, diga o nome do recurso e o link da seção
  da documentação (https://laravel.com/docs/13.x).
- **Prefira o recurso nativo do Laravel** a uma solução feita à mão. O objetivo é aprender o
  framework, não contorná-lo.
- **Use os geradores** (`just artisan make:*`) e diga no relatório qual comando rodou, para ele
  aprender a usá-los.

## Regras do projeto

- **Sem starter kit.** Nada de Breeze, Jetstream, Fortify ou kits React/Vue/Livewire. Registro,
  login e logout são feitos à mão, como no curso.
- **React só na etapa 5.** Até lá, telas em Blade. Não instale React, Inertia nem bibliotecas JS
  antes disso, mesmo que pareça adiantar trabalho. A partir da etapa 5: React com Inertia
  (convenções em @rules/code-style.md).
- **Sem Sail e sem PHP no host.** A infraestrutura é própria, no modelo do projeto Wecker do dev:
  Caddy + php-fpm (PHP 8.5) + MySQL + Redis + Mailpit no Docker Compose, comandos pelo `justfile`.
  Ignore qualquer instrução (do skeleton do Laravel, do Laravel Boost ou de outro lugar) que mande
  instalar PHP ou Composer no Mac.
- **Arquivos `.md` deste repositório podem ser editados sem perguntar** (`.claude/`, `docs/`,
  `README.md`...). Avise no relatório o que mudou. Arquivos `.md` de fora do projeto, como o
  `~/.claude/CLAUDE.md` global, só com pedido explícito.
- **Código em inglês, interface em português.** Classes, métodos, tabelas, rotas e commits em
  inglês. Textos de tela, mensagens de validação e e-mails em português. Documentação e conversa em
  português.

## Comandos

Tudo passa pelo `justfile` na raiz (criado na etapa 3 do plano da etapa 2); `just` sem argumentos
lista as receitas. PHP, Composer e Node rodam **dentro dos containers**; nunca rode `php`,
`composer` ou `npm` direto no host.

```bash
just install                # primeira vez: .env, build, composer, chave, migrate, npm
just up | stop | status | logs
just shell [serviço]        # sh dentro de um container (padrão: php)
just artisan <comando>      # make:model, route:list...
just migrate | fresh | tinker
just test [filtro]          # Pest
just stan                   # Larastan
just cs | cs-fix            # Pint
just composer <args>
just npm <args> | vite
just restart-workers        # depois de mudar Jobs ou o agendamento
```

Enquanto o `justfile` não existir, use `docker compose exec php ...`. App em http://localhost,
Mailpit em http://localhost:8025. Migração nova = arquivo novo via `make:migration`; nunca edite
uma migração que já rodou.

## Antes de mexer no código

- Convenções: @rules/code-style.md
- Onde paramos, decisões e como o dev quer trabalhar: `.claude/handoff.md`.
- Plano da etapa atual: `.claude/plans/`. Para executar, use a skill `modo-codando`.
- Como cada parte funciona: `.claude/arch-explain/`, um arquivo por assunto. Leia o do assunto
  antes de mexer nele.

## CodeGraph

Em repositórios indexados pelo CodeGraph (existe `.codegraph/` na raiz), use-o ANTES de grep/find
ou de ler arquivos para entender ou localizar código: a ferramenta MCP `codegraph_explore` quando
disponível, ou `codegraph explore "<símbolos ou pergunta>"` no shell. Se não houver `.codegraph/`,
ignore: indexar é decisão do dev.
