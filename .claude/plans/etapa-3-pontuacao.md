# Plano: etapa 3 do cronograma — Pontuação de observabilidade

## Objetivo

Mostrar, para um local do usuário, se o céu está bom para observar: uma pontuação de
observabilidade de 0 a 10 calculada a partir da cobertura de nuvens no momento da consulta
(Open-Meteo) e da fase da lua, numa tela "Céu agora" em Blade. Sem React e sem bibliotecas JS.

Pronto quando: a tela `sky` mostra a pontuação, a lua e as nuvens atuais do local escolhido;
a previsão fica em cache; o serviço externo é testado só com `Http::fake`; Pint, Larastan e Pest
verdes no CI.

## Ponto de partida

Plano da etapa 2 concluído: login próprio, model `Location` (vários por usuário, com `latitude`,
`longitude` e `timezone`), `LocationPolicy`, Pest, Larastan nível 5 e CI. Cache no Redis em dev
(`CACHE_STORE=redis` no `.env.example`) e `array` nos testes (`phpunit.xml`).

## Fatos sobre as fontes

- **Open-Meteo** (`https://api.open-meteo.com/v1/forecast`), sem chave. Parâmetros:
  `latitude`, `longitude` e `current=cloud_cover`. A resposta traz `current.cloud_cover` (0 a
  100) e `current.time`, atualizado a cada 15 minutos (`current.interval = 900`).
- **Lua:** pacote `solaris/php-moon-phase` (v3.4, MIT). `new Solaris\MoonPhase($data)` dá
  `getIllumination()` (0 = nova, 1 = cheia) e `getPhaseNameEnum()` (8 fases em inglês). Roda
  local, sem API.

## Decisões do dev

1. **Fórmula da pontuação de observabilidade:** começa em 10 e perde por dois motivos. As nuvens
   tiram na mesma proporção (30% de nuvens → fica 70% da pontuação; céu fechado → 0). A lua tira
   até metade (nova não tira nada, meia-lua tira um quarto, cheia tira metade).
   `pontuação = 10 × (1 − nuvens/100) × (1 − 0,5 × iluminação)`, com `nuvens` = cobertura no
   momento da consulta (decisão 4). Arredondada a uma casa decimal.
   **A regra precisa estar explicada nos comentários do `SkyScore` e na documentação**
   (`.claude/arch-explain/sky-score.md`), com a tabela de exemplos:

   | Céu | Lua | Pontuação |
   |---|---|---|
   | Limpo | Nova | 10 |
   | Limpo | Cheia | 5 |
   | Metade nublado | Nova | 5 |
   | Metade nublado | Cheia | 2,5 |
   | Fechado | Qualquer | 0 |

2. **Local na tela:** um por vez, com uma lista suspensa num formulário GET
   (`/sky?location=3`), sem JavaScript. Sem `location` na URL, abre o primeiro local em ordem
   alfabética.
3. **Cache:** 1 hora, com `Cache::remember`, chave com as coordenadas, então dois locais no
   mesmo ponto compartilham a resposta. A API atualiza a cada 15 minutos, mas a diferença em 1
   hora raramente é perceptível.
4. **Nuvens de agora:** o app usa só a cobertura de nuvens do momento da consulta
   (`current=cloud_cover`), seja dia ou noite.
5. **Nomes:** **pontuação de observabilidade** (classe `SkyScore`) e tela **"Céu agora"** (rota
   `GET /sky` com nome `sky`, `SkyController`, view `sky.blade.php`).
6. **Lua:** pacote `solaris/php-moon-phase`, com uma casca fina em `App\Services\MoonPhase`.

## Regras de sempre

- MVP: só as classes listadas; nada de interface, DTO ou camada "para o futuro".
- Classes de `app/Services/` recebem dependências pelo construtor (`Http` e `Cache` via
  `Illuminate\Http\Client\Factory` e `Illuminate\Contracts\Cache\Repository`); facades só em
  controller e testes.
- Testes sem rede: `Http::fake()` em tudo que chama o Open-Meteo, e `Http::preventStrayRequests()`
  nos testes do serviço para falhar se algo escapar.
- Nada de React, Inertia ou JS. Blade, como na etapa 2.

## Etapas de implementação

1. **Explorar o Open-Meteo e documentar.** Montar a URL com os parâmetros acima para um local de
   exemplo, para o dev abrir no navegador e ver a resposta (sessão 1 do cronograma). Criar
   `.claude/arch-explain/http-client.md`: o HTTP Client do Laravel (`Http::get`, `->throw()`,
   `->json()`), a URL do Open-Meteo, os campos usados e como `Http::fake` substitui a rede nos
   testes. Sem código de aplicação.
2. **`ForecastService`: nuvens de agora.** `app/Services/ForecastService.php` com
   `currentCloudCover(Location $location): int`, que devolve a cobertura de nuvens do momento
   (decisão 4). Chama o Open-Meteo pelo `Factory` injetado, com `->throw()` para erro HTTP virar
   exceção. Testes em `tests/Feature/ForecastServiceTest.php` (o `Http::fake` precisa do app; os
   testes de `tests/Unit` não sobem o Laravel): parâmetros enviados (`Http::assertSent`) e valor
   devolvido.
3. **`MoonPhase`: iluminação e nome da fase.** `just composer require solaris/php-moon-phase`.
   `app/Services/MoonPhase.php`, uma casca fina sobre o pacote:
   `illumination(CarbonInterface $moment): float` (0 a 1) e `name(CarbonInterface $moment): string`
   em português (as 8 fases do pacote viram Nova, Crescente, Cheia, Minguante). Unit test em `tests/Unit/MoonPhaseTest.php`
   com datas conhecidas (a lua nova de referência ≈ 0; uma lua cheia conhecida, como
   2025-01-13, ≈ 1), com tolerância.
4. **`SkyScore`: a pontuação de 0 a 10.** `app/Services/SkyScore.php`: recebe a cobertura de
   nuvens (0 a 100) e a iluminação da lua e devolve a pontuação (decisão 1), com comentário
   explicando a regra. Unit test com os cinco casos da tabela. Criar
   `.claude/arch-explain/sky-score.md` com a regra, a tabela e onde ela está no código.
5. **Cache da previsão.** No `ForecastService`, envolver a chamada ao Open-Meteo em
   `Cache::remember` (pelo `Repository` injetado) por 1 hora, chave com as coordenadas (decisão 3). Teste: duas chamadas seguidas fazem uma requisição só
   (`Http::assertSentCount(1)`). Atualizar `http-client.md` com a parte do cache.
6. **Tela "Céu agora".** Controller invocável `SkyController`
   (`make:controller SkyController --invokable`), rota `GET /sky` com nome `sky` no
   grupo `auth`. O local vem de `?location=` buscado dentro dos locais do usuário
   (`$request->user()->locations()->findOrFail(...)`), então id de outro usuário dá 404 sem
   precisar de método novo na Policy; sem parâmetro, o primeiro em ordem alfabética; sem nenhum
   local, um aviso com link para cadastrar. View `sky.blade.php` com a lista suspensa de
   locais, a pontuação em destaque, a fase e a iluminação da lua e a cobertura de nuvens atual.
   Link "Céu agora" no menu do `<x-layout>`. Feature tests com `Http::fake`: dono vê a pontuação
   do local; local de outro dá 404; troca de local pela URL; usuário sem local vê o aviso;
   visitante vai para o login.
7. **Falha do Open-Meteo.** Se a previsão não vier (erro HTTP ou de conexão), a tela mostra
   "Previsão indisponível agora, tente de novo em alguns minutos" em vez de erro 500, e nada vai
   para o cache. Feature test com `Http::fake` devolvendo 500 e com `Http::failedConnection()`.

Ao fim de cada etapa, o dev faz commit; ao fim da etapa 7, o PR. Depois, marcar as tarefas da
etapa 3 no cronograma.

## Fora deste plano

- Lua acima ou abaixo do horizonte (nascer e pôr da lua): a pontuação usa só a iluminação.
- Alerta por e-mail e Job agendado: etapa 4 do cronograma.
- Detectar o fuso pelo navegador: etapa 5 (React).

## Relação com o cronograma

| Sessão do cronograma | Etapas deste plano |
|---|---|
| 1. Estudar o HTTP Client e testar o Open-Meteo no navegador | 1 |
| 2. ForecastService buscando nuvens, com Http::fake | 2 |
| 3. FaseDaLua e PontuacaoObservabilidade, com testes | 3 e 4 |
| 4. Cache da previsão com Cache::remember | 5 |
| 5. Tela "Céu agora" em Blade | 6 e 7 |

## Progresso

- [x] 1. Explorar o Open-Meteo e documentar — confirmada em 2026-11-05
- [x] 2. `ForecastService`: nuvens de agora — confirmada em 2026-11-05
- [x] 3. `MoonPhase`: iluminação e nome da fase — confirmada em 2026-11-05
- [x] 4. `SkyScore`: a pontuação de 0 a 10 — confirmada em 2026-11-05
- [x] 5. Cache da previsão — confirmada em 2026-11-05
- [x] 6. Tela "Céu agora" — confirmada em 2026-11-05
- [x] 7. Falha do Open-Meteo — confirmada em 2026-11-05
