# Plano: etapa 4a do cronograma — Alerta por e-mail

## Objetivo

Todo dia às 20h (São Paulo), o app confere o céu de cada usuário e, se o melhor local dele tiver
pontuação de observabilidade 7 ou mais, manda um e-mail "o céu está bom". Tudo local: fila no
Redis, worker e scheduler no Compose, e-mail no Mailpit.

Pronto quando: o `CheckSkyJob` agendado às 20h manda o `GoodSkyAlert` só para quem passa do
mínimo; o e-mail chega no Mailpit em dev; Pint, Larastan e Pest verdes. O deploy fica para o
plano 4b.

## Ponto de partida

Plano da etapa 3 concluído: `ForecastService` (nuvens de agora, com cache de 1 hora),
`MoonPhase`, `SkyScore` e a tela "Céu agora". Compose de dev com `caddy`, `php`, `mysql`, `redis`
e `mailpit`; ainda **sem** worker da fila nem scheduler. Nos testes, fila `sync` e e-mail `array`
(`phpunit.xml`).

## Decisões do dev

1. **Horário:** uma vez por dia, às 20h de `America/Sao_Paulo`. Rodar uma vez só já garante "um
   e-mail por usuário por dia", sem coluna de controle de envio.
2. **Mínimo:** pontuação **7 ou mais**. Lua cheia (máximo 5) nunca gera alerta.
3. **Vários locais:** um e-mail por usuário, com o melhor local.
4. **Dois planos:** 4a (este, tudo local) e 4b (MVP no ar).

## Regras de sempre

- MVP: só as classes listadas.
- Testes sem rede: `Http::fake()`, `Mail::fake()`; `Http::preventStrayRequests()` nos testes que
  chamam o Open-Meteo.
- Facades podem ser usadas em Jobs; serviços entram pelo método `handle()` (o container resolve).

## Etapas de implementação

1. **Estudar e documentar filas, Jobs, Scheduler e Mail.** Criar
   `.claude/arch-explain/queues-and-mail.md`: Job e fila (o que vai para o Redis, o que o worker
   faz), `queue:work`, Scheduler (`routes/console.php`, `schedule:work`), Mailable e Mailpit, e
   como `Queue::fake`/`Mail::fake` funcionam nos testes. Sem código de aplicação.
2. **Worker e scheduler no Compose.** Serviços `queue` (`php artisan queue:work`) e `scheduler`
   (`php artisan schedule:work`) no `compose.yaml`, com a mesma imagem e volumes do `php`.
   Receita `just restart-workers` no `justfile` (já citada no `CLAUDE.md`). Validar com
   `docker compose config -q` e `just --list`; o dev roda `just up` e confere `just status`.
3. **Mailable `GoodSkyAlert`.** `make:mail GoodSkyAlert --markdown=mail.good-sky-alert`. Recebe o
   local, a pontuação, as nuvens e a fase da lua; assunto e texto em português, com link para a
   tela "Céu agora" daquele local (`route('sky', ['location' => ...])`). Teste: o e-mail renderizado
   mostra o nome do local, a pontuação e o link.
4. **`CheckSkyJob`.** `make:job CheckSkyJob`. Para cada usuário com locais: calcula a pontuação de
   cada local (`ForecastService`, `MoonPhase`, `SkyScore`), pega a melhor e, se for 7 ou mais,
   envia o `GoodSkyAlert`. Falha do Open-Meteo num local (`HttpClientException`) pula aquele
   local e registra `Log::warning`, sem derrubar o Job. Feature tests com `Http::fake` e
   `Mail::fake`: acima do mínimo envia; abaixo não envia; vários locais mandam um e-mail só, com
   o melhor; usuário sem local é ignorado; falha num local não impede os outros.
5. **Agendamento e teste no Mailpit.** Em `routes/console.php`,
   `Schedule::job(new CheckSkyJob)->dailyAt('20:00')->timezone('America/Sao_Paulo')`. Teste: o
   agendamento existe no horário certo. Conferência manual: `just artisan schedule:list` e
   disparar o Job no Tinker com um local de céu limpo; o e-mail aparece em http://localhost:8025.

Ao fim de cada etapa, o dev faz commit.

## Fora deste plano

- Deploy, imagem de produção e e-mail real (Resend): plano 4b.
- Escolher o horário por usuário, cancelar o alerta, histórico de alertas.

## Relação com o cronograma

| Sessão do cronograma | Etapas deste plano |
|---|---|
| 1. Estudar filas, Jobs, Scheduler e Mail | 1 |
| 2. CheckSkyJob com teste | 4 |
| 3. Mailable GoodSkyAlert, agendamento e teste com Mailpit | 2, 3 e 5 |

O Mailable vem antes do Job (etapa 3) para o teste do Job já poder conferir o e-mail enviado.

## Progresso

- [x] 1. Estudar e documentar filas, Jobs, Scheduler e Mail — confirmada em 2026-11-06
- [ ] 2. Worker e scheduler no Compose — entregue, aguardando conferência
- [ ] 3. Mailable `GoodSkyAlert`
- [ ] 4. `CheckSkyJob`
- [ ] 5. Agendamento e teste no Mailpit
