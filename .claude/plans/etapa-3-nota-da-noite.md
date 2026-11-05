# Plano: etapa 3 do cronograma — Nota da noite

## Objetivo

Mostrar, para um local do usuário, se a noite vai ser boa para observar o céu: uma nota de 0 a 10
calculada a partir da previsão de nuvens (Open-Meteo) e da fase da lua, numa tela "Hoje à noite"
em Blade. Sem React e sem bibliotecas JS.

Pronto quando: a tela `tonight` mostra a nota, a lua e as nuvens hora a hora do local escolhido;
a previsão fica 1 hora em cache; o serviço externo é testado só com `Http::fake`; Pint, Larastan
e Pest verdes no CI.

## Ponto de partida

Plano da etapa 2 concluído: login próprio, model `Location` (vários por usuário, com `latitude`,
`longitude` e `timezone`), `LocationPolicy`, Pest, Larastan nível 5 e CI. Cache no Redis em dev
(`CACHE_STORE=redis` no `.env.example`) e `array` nos testes (`phpunit.xml`).

## Fatos sobre as fontes

- **Open-Meteo** (`https://api.open-meteo.com/v1/forecast`), sem chave. Parâmetros que importam:
  `latitude`, `longitude`, `hourly=cloud_cover`, `timezone` (devolve as horas já no fuso do
  local), `past_days=1` (para a noite que começou ontem) e `forecast_days=2` (para a madrugada
  de amanhã). A resposta traz `hourly.time` e `hourly.cloud_cover` (0 a 100), em listas
  paralelas. Conferir na etapa 1 antes de escrever o serviço.
- **Lua:** a iluminação (0 = nova, 1 = cheia) sai de uma conta com a data: idade da lua =
  dias desde uma lua nova conhecida (2000-01-06 18:14 UTC), módulo 29,530588853 (o mês lunar);
  iluminação = (1 − cos(2π × idade / 29,53)) / 2. Erro de algumas horas, irrelevante para a nota.
  Nenhuma API.

## Decisões do dev (2026-11-04)

1. **Fórmula da nota:** começa em 10 e perde por dois motivos. As nuvens tiram na mesma
   proporção (30% de nuvens → fica 70% da nota; céu fechado → 0). A lua tira até metade (nova
   não tira nada, meia-lua tira um quarto, cheia tira metade).
   `nota = 10 × (1 − nuvens/100) × (1 − 0,5 × iluminação)`, com `nuvens` = média da cobertura
   nas horas da noite. Arredondada a uma casa decimal.
   **A regra precisa estar explicada nos comentários do `NightScore` e na documentação**
   (`.claude/arch-explain/night-score.md`), com a tabela de exemplos:

   | Céu | Lua | Nota |
   |---|---|---|
   | Limpo | Nova | 10 |
   | Limpo | Cheia | 5 |
   | Metade nublado | Nova | 5 |
   | Metade nublado | Cheia | 2,5 |
   | Fechado | Qualquer | 0 |

2. **Local na tela:** um por vez, com uma lista suspensa num formulário GET
   (`/tonight?location=3`), sem JavaScript. Sem `location` na URL, abre o primeiro local em ordem
   alfabética.
3. **Cache:** 1 hora, com `Cache::remember`. A chave leva as coordenadas e a data de início da
   noite, então dois locais no mesmo ponto compartilham a resposta.
4. **Madrugada:** antes das 6h no fuso do local, "hoje à noite" é a noite em curso, que começou
   às 18h do dia anterior. A partir das 6h, é a noite que começa às 18h do dia.

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
2. **`ForecastService`: nuvens das horas da noite.** `app/Services/ForecastService.php` com um
   método público que recebe o `Location` e o momento atual e devolve a cobertura de nuvens
   (hora local → %) das horas da noite: das 18h às 6h no fuso do local, com a regra da madrugada
   (decisão 4). Chama o Open-Meteo pelo `Factory` injetado, com `->throw()` para erro HTTP virar
   exceção. Testes em `tests/Feature/ForecastServiceTest.php` (o `Http::fake` precisa do app; os
   testes de `tests/Unit` não sobem o Laravel): parâmetros enviados (`Http::assertSent`), recorte
   das 18h às 6h, regra da madrugada (momento fixo antes das 6h) e fuso respeitado.
3. **`MoonPhase`: iluminação e nome da fase.** `app/Services/MoonPhase.php`, sem dependências:
   `illumination(CarbonInterface $moment): float` (0 a 1) e `name(CarbonInterface $moment): string`
   em português (Nova, Crescente, Cheia, Minguante). Unit test em `tests/Unit/MoonPhaseTest.php`
   com datas conhecidas (a lua nova de referência ≈ 0; uma lua cheia conhecida, como
   2025-01-13, ≈ 1), com tolerância.
4. **`NightScore`: a nota de 0 a 10.** `app/Services/NightScore.php`: recebe a lista de coberturas
   de nuvens e a iluminação da lua e devolve a nota (decisão 1), com comentário explicando a
   regra. Lista vazia de horas é erro (exceção), não nota 10. Unit test com os cinco casos da
   tabela. Criar `.claude/arch-explain/night-score.md` com a regra, a tabela e onde ela está no
   código.
5. **Cache da previsão.** No `ForecastService`, envolver a chamada ao Open-Meteo em
   `Cache::remember` (pelo `Repository` injetado) por 1 hora, chave com coordenadas e data de
   início da noite (decisão 3). Teste: duas chamadas seguidas fazem uma requisição só
   (`Http::assertSentCount(1)`). Atualizar `http-client.md` com a parte do cache.
6. **Tela "Hoje à noite".** Controller invocável `TonightController`
   (`make:controller TonightController --invokable`), rota `GET /tonight` com nome `tonight` no
   grupo `auth`. O local vem de `?location=` buscado dentro dos locais do usuário
   (`$request->user()->locations()->findOrFail(...)`), então id de outro usuário dá 404 sem
   precisar de método novo na Policy; sem parâmetro, o primeiro em ordem alfabética; sem nenhum
   local, um aviso com link para cadastrar. View `tonight.blade.php` com a lista suspensa de
   locais, a nota em destaque, a fase e a iluminação da lua e uma tabela de nuvens hora a hora.
   Link "Hoje à noite" no menu do `<x-layout>`. Feature tests com `Http::fake`: dono vê a nota
   do local; local de outro dá 404; troca de local pela URL; usuário sem local vê o aviso;
   visitante vai para o login.
7. **Falha do Open-Meteo.** Se a previsão não vier (erro HTTP ou de conexão), a tela mostra
   "Previsão indisponível agora, tente de novo em alguns minutos" em vez de erro 500, e nada vai
   para o cache. Feature test com `Http::fake` devolvendo 500 e com `Http::failedConnection()`.

Ao fim de cada etapa, o dev faz commit; ao fim da etapa 7, o PR. Depois, marcar as tarefas da
etapa 3 no cronograma.

## Fora deste plano

- Lua acima ou abaixo do horizonte (nascer e pôr da lua): a nota usa só a iluminação.
- Alerta por e-mail e Job agendado: etapa 4 do cronograma.
- Detectar o fuso pelo navegador e qualquer gráfico: etapas 5 e 6 (React).

## Relação com o cronograma

| Sessão do cronograma | Etapas deste plano |
|---|---|
| 1. Estudar o HTTP Client e testar o Open-Meteo no navegador | 1 |
| 2. ForecastService buscando nuvens por hora, com Http::fake | 2 |
| 3. MoonPhase e NightScore, com testes | 3 e 4 |
| 4. Cache da previsão com Cache::remember | 5 |
| 5. Tela "Hoje à noite" em Blade | 6 e 7 |

## Progresso

- [ ] 1. Explorar o Open-Meteo e documentar — entregue, aguardando conferência
- [ ] 2. `ForecastService`: nuvens das horas da noite
- [ ] 3. `MoonPhase`: iluminação e nome da fase
- [ ] 4. `NightScore`: a nota de 0 a 10
- [ ] 5. Cache da previsão
- [ ] 6. Tela "Hoje à noite"
- [ ] 7. Falha do Open-Meteo
