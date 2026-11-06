# Filas, Jobs, Scheduler e Mail

## O que é

Quatro peças que trabalham juntas no alerta da etapa 4a:

- **Job** ([docs](https://laravel.com/docs/13.x/queues)): uma classe com um método `handle()` que
  descreve uma tarefa para rodar **depois**, fora da requisição.
- **Fila e worker:** o Job é serializado e guardado no Redis; um processo à parte
  (`php artisan queue:work`) tira da fila e executa.
- **Scheduler** ([docs](https://laravel.com/docs/13.x/scheduling)): o "cron" do Laravel, escrito
  em PHP no `routes/console.php`.
- **Mailable** ([docs](https://laravel.com/docs/13.x/mail)): uma classe por e-mail (assunto,
  template, dados), enviada pela facade `Mail`. Em dev, tudo vai para o Mailpit.

## Analogia

Uma **padaria**. O Scheduler é o **despertador** que toca às 20h e prende um pedido no
**varal de comandas** (a fila no Redis). O worker é o **padeiro** que fica de olho no varal,
pega a comanda e faz o serviço (o `handle()` do Job). O Mailable é a **embalagem com etiqueta**
que sai para a entrega; em dev, o entregador deixa tudo no **balcão dos fundos** (Mailpit), e
nada chega ao cliente de verdade.

Quem toca o despertador não faz o pão: o Scheduler só **enfileira**; quem executa é o worker.

## Como funciona aqui

### Job e fila

```bash
just artisan make:job CheckSkyJob    # gera app/Jobs/CheckSkyJob.php
```

```php
class CheckSkyJob implements ShouldQueue
{
    use Queueable;

    public function handle(ForecastService $forecast, MoonPhase $moon, SkyScore $score): void
    {
        // ...
    }
}
```

- **`ShouldQueue`** é o que diz "não rode agora, mande para a fila". Sem ele, o Job roda na hora,
  dentro de quem o disparou.
- **`handle()` recebe dependências pelo tipo**: o container resolve na hora de executar, como no
  construtor de um controller. Por isso os serviços entram no `handle()`, e o construtor do Job
  fica só para **dados** (que precisam ser serializados).
- **O que vai para o Redis** é o objeto do Job serializado (classe + propriedades), não o código.
  Um model passado ao construtor vira só "classe + id" e é buscado de novo no banco quando o Job
  roda (trait `SerializesModels`, incluída no `Queueable`).
- **Disparar:** `CheckSkyJob::dispatch()` (vai para a fila) ou `CheckSkyJob::dispatchSync()`
  (roda agora, útil no Tinker).
- **Qual fila:** `QUEUE_CONNECTION` no `.env` (`redis` em dev, `sync` nos testes, que roda o Job
  na hora). Jobs que falham depois das tentativas vão para a tabela `failed_jobs`.

**Ponte com o PHP puro:** é o que você faria com um `LPUSH` de um JSON no Redis e um script em
loop com `BRPOP` rodando em background. O Laravel cuida da serialização, das tentativas, do
timeout e do registro de falhas.

### Por dentro do Redis

**O Redis em si.** Um servidor (porta 6379) que guarda chave → valor **na memória**. Sem tabelas
nem SQL; comandos curtos (`SET`, `GET`, `RPUSH`...). Atende **um comando por vez**, então cada
comando é atômico. Se o MySQL é o **arquivo morto** (organizado, com SQL, mas é preciso ir até a
sala), o Redis é o **quadro de avisos ao lado da porta**: rápido, para coisas pequenas e de vida
curta.

**O valor tem tipo**, e cada tipo tem seus comandos:

| Tipo | Analogia | Comandos | Quem usa aqui |
|---|---|---|---|
| string | post-it | `SET`, `GET`, `EXPIRE` | sessão e cache |
| list | varal: entra numa ponta, sai na outra | `RPUSH`, `LPOP`, `LRANGE` | a fila |
| sorted set | lista de nomes com horário, sempre ordenada | `ZADD`, `ZRANGEBYSCORE`, `ZREM` | `reserved` e `delayed` da fila |

**Sessão e cache** são post-its com prazo: `SET <prefixo><id-da-sessão> "<dados>" EX 7200`. O `EX`
é o TTL: o Redis apaga a chave sozinho. O prefixo (`REDIS_PREFIX`, derivado do `APP_NAME` em
`config/database.php`) separa as chaves deste app das de outros.

**Um Redis, dois bancos:** sessão e fila no banco 0 (`REDIS_DB`), cache no banco 1
(`REDIS_CACHE_DB`). Por isso `cache:clear` não derruba sessões nem Jobs.

**A fila** usa três chaves (fila `default`):

| Chave | Tipo | Na padaria |
|---|---|---|
| `queues:default` | list | o varal: comandas esperando |
| `queues:default:reserved` | sorted set | comandas na mão do padeiro, com cronômetro |
| `queues:default:delayed` | sorted set | o quadro "não antes das X horas" |

O caminho de uma comanda:

1. `dispatch()` serializa o Job num JSON e faz `RPUSH` no varal.
2. O worker tira do varal e, **na mesma operação**, põe em `reserved` com prazo de 90 s
   (`retry_after` em `config/queue.php`).
3. Deu certo: apaga do `reserved`.
4. Exceção: conta a tentativa e devolve ao varal (ou ao `delayed`). Acabaram as tentativas: vai
   para a tabela `failed_jobs` no MySQL.
5. O worker morreu no meio: o prazo vence e outro worker devolve a comanda ao varal. Nenhum Job
   se perde. É para isso que existe o `reserved`; com um `LPOP` simples, ele sumiria.

A comanda diz **qual receita e com quais ingredientes**, não traz a receita:

```json
{
  "displayName": "App\\Jobs\\CheckSkyJob",
  "attempts": 0,
  "data": { "command": "O:21:\"App\\Jobs\\CheckSkyJob\":0:{}" }
}
```

O código do `handle()` está no livro do padeiro (o app carregado no worker).

**Em PHP puro** seria um `rPush` de um lado e um `while (true) { blPop(...) }` do outro; o
`queue:work` é esse laço com reserva, tentativas, timeout e registro de falhas.

### Worker

`php artisan queue:work` ([docs](https://laravel.com/docs/13.x/queues#running-the-queue-worker))
é um processo que **não termina**: fica esperando Jobs. Ele carrega o app **uma vez** e reaproveita
a memória entre os Jobs. No Compose vira um serviço próprio (`queue`), com a mesma imagem do `php`.

### Scheduler

```php
// routes/console.php
Schedule::job(new CheckSkyJob)->dailyAt('20:00')->timezone('America/Sao_Paulo');
```

- **Um só cron:** em produção clássica, o crontab tem uma linha que roda `schedule:run` a cada
  minuto, e o Laravel decide o que está na hora. Aqui, em vez do cron, o serviço `scheduler` roda
  `php artisan schedule:work`, que faz esse "a cada minuto" sozinho.
- **`Schedule::job()`** só põe o Job na fila; o worker executa. (`Schedule::call()` e
  `Schedule::command()` rodam dentro do próprio scheduler.)
- **`->timezone()`**: sem ele, `20:00` é no fuso do app (`APP_TIMEZONE`, padrão UTC), ou seja,
  17h em São Paulo.

### Mailable e Mailpit

```bash
just artisan make:mail GoodSkyAlert --markdown=mail.good-sky-alert
# gera app/Mail/GoodSkyAlert.php e resources/views/mail/good-sky-alert.blade.php
```

- **A classe:** `envelope()` define o assunto, `content()` aponta o template; as propriedades
  públicas do construtor ficam disponíveis no template, como as variáveis de uma view.
- **Markdown** ([docs](https://laravel.com/docs/13.x/mail#markdown-mailables)): o template usa
  componentes prontos (`<x-mail::message>`, `<x-mail::button>`) e o Laravel gera HTML responsivo
  e a versão em texto.
- **Enviar:** `Mail::to($user)->send(new GoodSkyAlert(...))`. Dentro do Job usamos `send()`: o
  Job já roda fora da requisição, então não faz sentido enfileirar de novo (`queue()`).
- **Mailpit:** em dev, `MAIL_MAILER=smtp`, `MAIL_HOST=mailpit` e porta `1025` (o SMTP dele). A
  interface fica em http://localhost:8025.
- **Links no e-mail:** `route('sky', ...)` usa o `APP_URL` para montar a URL completa, já que não
  há requisição de onde tirar o domínio.

### Nos testes

`phpunit.xml` já define `QUEUE_CONNECTION=sync` e `MAIL_MAILER=array`. Por cima disso, os fakes:

```php
Mail::fake();

(new CheckSkyJob)->handle(...);    // ou app()->call([new CheckSkyJob, 'handle'])

Mail::assertSent(GoodSkyAlert::class, fn (GoodSkyAlert $mail) => $mail->hasTo($user->email));
Mail::assertSentCount(1);
Mail::assertNothingSent();
```

- **`Mail::fake()`** troca o carteiro por um que só anota; nada é renderizado nem enviado. As
  asserções conferem o que foi "enviado".
- **`Queue::fake()`** faz o mesmo com a fila: `dispatch()` só anota, e o teste confere com
  `Queue::assertPushed(CheckSkyJob::class)`. Serve para testar **quem dispara** o Job, não o que
  o Job faz.
- **Testar o e-mail em si:** cria-se o Mailable e confere o conteúdo renderizado, sem enviar:
  `$mailable->assertSeeInHtml('Sítio')`, `assertHasSubject(...)`
  ([docs](https://laravel.com/docs/13.x/mail#testing-mailable-content)).
- **Testar o agendamento:** busca-se o evento no `Schedule` do container e confere a expressão
  cron e o fuso.

## Pegadinhas

- **O worker não vê código novo.** Ele carregou o app na memória ao subir; depois de mudar um Job
  ou um serviço que ele usa, rode `just restart-workers` (`queue:restart` avisa o worker para
  terminar o Job atual e reiniciar).
- **`send()` × `queue()` no `Mail::fake()`:** um Mailable enviado com `queue()` (ou que implemente
  `ShouldQueue`) aparece em `assertQueued`, não em `assertSent`.
- **Construtor do Job só com dados simples ou models.** Um serviço ou uma conexão no construtor
  seria serializado para o Redis; dependências vão no `handle()`.
- **Fuso duplo:** o horário do agendamento (`->timezone()`) e o `APP_TIMEZONE` são coisas
  diferentes. O Job roda às 20h de São Paulo mesmo com o app em UTC.
- **`sync` esconde problemas de serialização.** Nos testes o Job nem passa pelo Redis; um erro de
  serialização só aparece em dev, com o worker.

## Comandos úteis

```bash
just artisan schedule:list           # o que está agendado e a próxima execução
just artisan schedule:test           # escolhe uma tarefa agendada e roda agora
just artisan queue:failed            # Jobs que falharam
just artisan queue:retry all         # tenta de novo os que falharam
just restart-workers                 # depois de mudar Jobs ou o agendamento

# ver a fila por dentro (com o worker parado, depois de um dispatch)
just shell redis
redis-cli KEYS '*queues*'
redis-cli LRANGE <chave-que-apareceu> 0 -1

just tinker
> App\Jobs\CheckSkyJob::dispatchSync()
```
