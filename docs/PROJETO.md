# Céu de Hoje

Um app que diz se o céu está bom para observar. Cada usuário cadastra o local de onde
observa; o app cruza a cobertura de nuvens com a fase da lua, dá uma pontuação de observabilidade
de 0 a 10,
avisa por e-mail quando ela vale a pena e mostra quais planetas estarão visíveis.

É um projeto de estudo e de portfólio: o objetivo é aprender Laravel e React fazendo um app pequeno,
completo e publicado, que dê para explicar para qualquer um.

## Stack

- **Laravel 13** com **PHP 8.5**, em infraestrutura Docker própria no modelo do [Wecker](http://github.com/dancarvalhodev/wecker): **Caddy**
  na frente, **php-fpm** (Ubuntu 24.04), worker da fila e scheduler, tudo no Docker Compose e
  comandado pelo `justfile`. Sem Sail.
- **MySQL** (dados), **Redis** (sessão, cache e fila), **Mailpit** (e-mail em desenvolvimento).
- **Blade** até a etapa 4. **React + Inertia** a partir da etapa 5. Vite para os assets.
- **Pest** (testes), **Pint** (padrão de código), **Larastan** (análise estática).
- **GitHub Actions** para CI. Deploy em instância ARM na **Oracle Cloud**.
- Fase da lua: pacote **solaris/php-moon-phase** (cálculo local, sem API).
- APIs externas: **Open-Meteo** (cobertura de nuvens atual, sem chave) e **astronomy-engine**
  (cálculo de planetas no navegador, etapa 7).

## O que não entra

- Starter kit (Breeze, Jetstream, kits React/Vue/Livewire) e Fortify. O login é feito à mão, como
  no curso Getting Started with Laravel.
- React, Inertia ou qualquer framework JS antes da etapa 5. A única exceção é o botão "Usar minha
  localização" do cadastro de local, em JS puro (`resources/js/geolocation.js`).
- Login social, recuperação de senha, verificação de e-mail, painel de admin, API pública,
  multi-idioma. Podem virar versão 2.

## Escopo por etapa

| Etapa | Entrega | Pronto quando |
|---|---|---|
| 2. Base do projeto | Projeto e infra Docker (modelo Wecker); registro, login e logout; Pest; CI com Pest, Pint e Larastan; model `Location` com cadastro, Form Request e Policy | Usuário logado cadastra o próprio local e não vê o de outro; CI verde |
| 3. Pontuação de observabilidade | `ForecastService` (Open-Meteo), `MoonPhase`, `SkyScore` (0 a 10), cache, tela "Céu agora" em Blade | A tela mostra pontuação, lua e nuvens atuais do local do usuário; serviço externo testado com `Http::fake` |
| 4. Alerta e MVP no ar | `CheckSkyJob` agendado (horário no plano da etapa 4), Mailable `GoodSkyAlert`, imagem de produção e deploy com worker e scheduler | E-mail chega no Mailpit em dev; app rodando na Oracle |
| 5. React do zero | Inertia + React instalados; tela de cadastro do local convertida | Tela em React com os testes passando |
| 6. Painel em React | Componentes `ScoreCard` e `MoonPhase`, cobertura de nuvens atual, responsivo | Página "Céu agora" inteira em React, publicada |
| 7. Planetas visíveis | Hook `useVisiblePlanets` com astronomy-engine, lista e melhor horário | Planetas acima do horizonte à noite no painel |
| 8. Acabamento | README com prints, decisões e diagrama; GIF de demonstração | Projeto apresentável no GitHub |

Cada etapa ganha seu plano em `.claude/plans/` quando chegar a vez dela.

## Regras de negócio

- **Local:** nome, latitude (-90 a 90), longitude (-180 a 180) e fuso horário (identificador IANA,
  ex.: `America/Sao_Paulo`). Pertence a um usuário; ninguém vê nem altera o local de outro.
- **Pontuação de observabilidade (etapa 3):** de 0 a 10, combina a cobertura de nuvens no momento
  da consulta com a iluminação da lua. Fórmula no plano da etapa 3 e em
  `.claude/arch-explain/sky-score.md`.
- **Alerta (etapa 4):** todo dia às 20h (São Paulo), um e-mail por usuário quando o melhor local
  dele tem pontuação 7 ou mais.

## Nomes: cronograma × código

O cronograma está em português; o código fica em inglês para seguir as convenções do Laravel.

| No cronograma | No código |
|---|---|
| Local | `App\Models\Location` (tabela `locations`) |
| PrevisaoService | `App\Services\ForecastService` |
| FaseDaLua | `App\Services\MoonPhase` |
| PontuacaoObservabilidade | `App\Services\SkyScore` |
| Job VerificarCeu | `App\Jobs\CheckSkyJob` |
| Mailable AlertaCeuBom | `App\Mail\GoodSkyAlert` |
| CartaoPontuacao / FaseLua (React) | `ScoreCard.jsx` / `MoonPhase.jsx` |
| usePlanetasVisiveis | `useVisiblePlanets` |
| Tela "Céu agora" | rota `sky` |

Textos de interface, mensagens de validação e e-mails ficam em português.
