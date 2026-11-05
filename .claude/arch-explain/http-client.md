# HTTP Client (e o Open-Meteo)

## O que é

O [HTTP Client](https://laravel.com/docs/13.x/http-client) do Laravel é uma camada fina sobre o
Guzzle. Em vez de montar `new Client()`, `RequestOptions` e `json_decode` à mão, você escreve
`Http::get($url, $query)` e recebe um objeto `Response` com atalhos para status e JSON.

## Analogia

O Guzzle é a **cozinha completa**; o `Http` é o **balcão do restaurante**: você faz o pedido numa
linha e recebe o prato pronto. E nos testes dá para trocar a cozinha por uma de mentira
(`Http::fake`) sem que o cliente perceba.

## Como funciona aqui

- **A chamada:**

  ```php
  $response = $http->get('https://api.open-meteo.com/v1/forecast', [
      'latitude' => $location->latitude,
      'longitude' => $location->longitude,
      'current' => 'cloud_cover',
  ])->throw();

  $cloudCover = $response->json('current.cloud_cover');
  ```

  - O segundo argumento do `get()` vira a query string.
  - **`->throw()`**: o client **não** lança exceção em 4xx/5xx por padrão (diferente do Guzzle).
    O `throw()` faz um erro HTTP virar `RequestException`. Falha de conexão já vira
    `ConnectionException` sozinha.
  - **`->json('current.cloud_cover')`**: decodifica e pega a chave em notação de ponto (o mesmo
    `data_get` do resto do Laravel). Sem argumento, devolve o array inteiro.

### Facade, helper e construtor

Quem faz a requisição é um objeto da classe `Illuminate\Http\Client\Factory`, guardado no
container. Há três portas para chegar a ele.

**Analogia:** pense no ramal de uma empresa. Você disca "200 — Financeiro" sem saber quem está
sentado naquela mesa hoje; quem estiver lá atende. A **facade** é o ramal: `Http::get()` liga
para "a mesa do HTTP", e quem atende é o objeto que o container colocou ali. No teste, o
`Http::fake()` põe um substituto na mesa; quem disca o ramal nem percebe a troca.

Compare com um **helper comum** seu (`formatDate()`): ele não é um ramal, é a própria pessoa. O
código está na função, e não há mesa onde pôr um substituto.

As três portas:

| Porta | Exemplo | Onde usar aqui |
|---|---|---|
| Facade | `Http::get(...)` | Controller e testes |
| Helper do Laravel | `cache()->get('x')` (não existe `http()`) | Mesmo papel da facade; o projeto prefere a facade |
| Construtor | `__construct(private Factory $http)` | Classes de `app/Services/` |

- **Facade** ([docs](https://laravel.com/docs/13.x/facades)): um proxy estático. A classe
  `Http` não tem o código do `get()`; ela só repassa a chamada para o objeto do container.
- **Helpers** como `cache()`, `request()` e `auth()` são outro ramal para a mesma mesa:
  `cache()->get('x')` e `Cache::get('x')` fazem a mesma coisa
  ([helpers](https://laravel.com/docs/13.x/helpers)).
- **Construtor:** o `ForecastService` pede o objeto pelo tipo, e o container entrega sozinho
  ([injeção automática](https://laravel.com/docs/13.x/container#zero-configuration-resolution)).
  No Laminas você registraria uma factory no `ServiceManager`; aqui não há registro.

  ```php
  public function __construct(private Factory $http) {}

  // depois: $this->http->get(...)
  ```

As três chegam ao **mesmo objeto**, então um `Http::fake()` no teste vale para todas.

**Ponte com o PHP-DI (Wecker):**

- **Construtor = autowiring do PHP-DI.** O container do Laravel lê o tipo no construtor e
  entrega a instância, como o PHP-DI faz.
- **Definições = Service Provider.** O que no PHP-DI é uma definição
  (`Interface::class => autowire(Impl::class)` ou uma closure) no Laravel é um
  `$this->app->bind(...)` ou `singleton(...)` dentro de um Service Provider
  ([binding](https://laravel.com/docs/13.x/container#binding)).
- **Facade = um `$container->get()` estático e global.** O PHP-DI não tem facade; o mais
  próximo seria um *service locator*, que lá é considerado má prática por esconder
  dependências. O Laravel adota o atalho e resolve a parte do teste com `fake()`/`swap()`. A
  desvantagem de esconder dependências continua, por isso o projeto deixa a facade na borda e
  usa o construtor em `app/Services/`.

### O Open-Meteo

URL de exemplo (São Paulo), para abrir no navegador:

```
https://api.open-meteo.com/v1/forecast?latitude=-23.55&longitude=-46.63&current=cloud_cover
```

- Sem chave. `current=cloud_cover` pede só a cobertura de nuvens **de agora**; o app não recorta
  horas nem calcula nada.
- O que usamos da resposta:

  ```json
  {
    "current": {
      "time": "2026-11-05T07:45",
      "interval": 900,
      "cloud_cover": 92
    }
  }
  ```

  `cloud_cover` vai de 0 a 100 (%). `interval: 900` diz que o valor é atualizado a cada 15
  minutos. Sem o parâmetro `timezone`, o `time` vem em UTC; o app não usa esse campo.

### Cache da resposta

A resposta do Open-Meteo fica **1 hora** no cache (Redis em dev, `array` nos testes):

```php
return $this->cache->remember($cacheKey, self::CACHE_SECONDS, function () use ($location) {
    // ... chamada ao Open-Meteo ...
});
```

- **`remember(chave, segundos, closure)`** ([docs](https://laravel.com/docs/13.x/cache#retrieve-store)):
  se a chave existe, devolve o valor guardado sem rodar a closure; se não, roda a closure, guarda
  o resultado e devolve. É o "busca, e se não tiver, calcula e guarda" que você faria à mão com
  `get` + `set` no Redis.
- **Chave:** `cloud-cover:{latitude},{longitude}`. Dois locais no mesmo ponto (de usuários
  diferentes, inclusive) usam a mesma resposta.
- **Se a closure lançar exceção** (o `->throw()` de um 500, uma falha de conexão), nada é
  guardado: a próxima consulta tenta a API de novo.
- **`Repository` pelo construtor:** `Illuminate\Contracts\Cache\Repository` é o que a facade
  `Cache` esconde, como o `Factory` é o que a `Http` esconde. O driver (Redis, array) vem do
  `CACHE_STORE`; o serviço não sabe qual é.

### Nos testes

```php
Http::preventStrayRequests();

Http::fake([
    'api.open-meteo.com/*' => Http::response(['current' => ['cloud_cover' => 92]]),
]);

// ... chama o serviço ...

Http::assertSent(fn (Request $request) => $request['current'] === 'cloud_cover');
```

- **`Http::fake([...])`**: padrão de URL → resposta falsa. Nada sai para a rede.
- **`Http::preventStrayRequests()`**: qualquer requisição sem fake vira exceção, em vez de ir
  para a internet sem ninguém perceber.
- **`Http::assertSent()`** / **`assertSentCount()`**: confere o que foi pedido. `$request['x']`
  lê um parâmetro da query.
- O fake precisa do app de pé, por isso esses testes ficam em `tests/Feature`.

## Pegadinhas

- **Facade também é testável.** O `ForecastService` não recebe o `Factory` pelo construtor por
  causa dos testes (o `Http::fake()` funcionaria com a facade também). O motivo é clareza: o
  construtor mostra do que a classe depende, e ela não fica presa ao estado global do app
  ([facades × injeção](https://laravel.com/docs/13.x/facades#facades-vs-dependency-injection)).
- **Sem `->throw()`, um 500 passa calado** e o `json()` devolve `null` ou a página de erro.
- **Coordenadas arredondadas:** a API responde com o ponto da grade mais próximo
  (`-23.514938` em vez de `-23.55`). Não use o `latitude` da resposta para nada.

- **Cache vazio em cada teste:** o driver `array` vive só na memória do teste, então um teste não
  herda o cache do outro. Em dev (Redis), o valor fica 1 hora; para forçar uma consulta nova,
  `just artisan cache:clear`.

## Comandos úteis

```bash
just tinker
> Http::get('https://api.open-meteo.com/v1/forecast', ['latitude' => -23.55, 'longitude' => -46.63, 'current' => 'cloud_cover'])->json('current.cloud_cover')
```
