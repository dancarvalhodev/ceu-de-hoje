# Autorização (Policy)

## O que é

Autenticação pergunta **quem é você** (middleware `auth`); autorização pergunta **você pode mexer
nisto?**. A resposta para cada model fica numa Policy: `app/Policies/LocationPolicy.php`.

## Analogia

O porteiro (`auth`) deixa o morador entrar no prédio; a **fechadura** (Policy) decide qual
apartamento a chave abre.

## Como funciona aqui

- **Gerada com `just artisan make:policy LocationPolicy --model=Location`.** Ficaram só `update`
  e `delete`, que comparam `$user->id === $location->user_id`. Não há `view` porque não existe
  tela de detalhe (`show`), e a lista já filtra pelo usuário.
- **Descoberta automática** ([docs](https://laravel.com/docs/13.x/authorization#policy-discovery)):
  ao ouvir `Gate::authorize('update', $location)`, o Gate pega a classe do model
  (`App\Models\Location`), monta os nomes candidatos e usa o primeiro que existir:
  `App\Models\Policies\LocationPolicy` e depois `App\Policies\LocationPolicy`. Achou a classe,
  chama o método de mesmo nome da habilidade (`update`) com o usuário logado e o model. Nada é
  registrado: o `make:policy` já cria o arquivo no lugar e com o nome que o Gate procura. É como
  uma **lista telefônica por sobrenome**: o Gate sabe onde procurar "Location" + "Policy".
  No Laminas, você registraria a asserção numa config ou factory.
- **Fugir da convenção** (Policy com outro nome ou em outra pasta): `#[UsePolicy(MinhaPolicy::class)]`
  no model, ou `Gate::policy(Location::class, MinhaPolicy::class)` no `boot()` do
  `AppServiceProvider`.
- **`Gate::authorize('update', $location)`** no `edit`/`update` e `'delete'` no `destroy`: chama
  o método da Policy com o usuário logado; se der `false`, lança a exceção que vira **403**.
- **O `index` não precisa de Policy:** `$request->user()->locations()` já só busca os do usuário.

## Pegadinhas

- **Validação roda antes da Policy no `update`.** O Form Request valida antes do corpo do
  controller; um intruso que mandar dados inválidos recebe os erros de validação, não 403. Nada
  é salvo de qualquer jeito. Para inverter, use o middleware `can:update,location` na rota.
- **Policy sem o método pedido nega.** Chamar `Gate::authorize('view', ...)` hoje dá 403 para
  todos.
- **Teste os dois lados:** o dono passa (prova que a Policy foi encontrada) e o intruso leva 403.

## Comandos úteis

```bash
just artisan make:policy NomePolicy --model=Nome
just test LocationTest
```
