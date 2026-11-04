# Route model binding

## O que é

O Laravel transforma o pedaço variável da URL (`{location}`) no model já buscado no banco e o
entrega pronto ao controller. Sem `find()` no controller, sem `if (! $location) abort(404)`.
Documentação: https://laravel.com/docs/13.x/routing#route-model-binding

## Analogia

O **guarda-volumes**: você entrega a ficha número 7 (o id na URL) e recebe a mala (o model). Se
não existe mala 7, o atendente já responde "não temos" (404) e nem chama você para dentro.

## Como funciona aqui

```php
Route::resource('locations', LocationController::class)->except('show');
// gera, entre outras: GET /locations/{location}/edit

public function edit(Location $location)   // GET /locations/7/edit
```

1. A rota tem o parâmetro `{location}`.
2. O método tem um parâmetro **com o mesmo nome** (`$location`) **e tipo de model**
   (`Location`).
3. O middleware `SubstituteBindings` (já no grupo `web`) roda
   `Location::where('id', 7)->firstOrFail()` e põe o resultado no lugar do `7`.
4. Não achou? `firstOrFail()` lança `ModelNotFoundException`, que vira **404**.

As duas condições do passo 2 são a convenção: nome igual e type-hint de model. Com outro nome
(`Location $local`) o Laravel não faz o vínculo e entrega um model vazio.

## Pegadinhas

- **Binding não é autorização.** Ele busca o local 7 de qualquer usuário; quem barra o intruso é
  a Policy (`Gate::authorize`, ver `authorization.md`).
- **Buscar por outra coluna:** `{location:slug}` na rota, ou o atributo `#[RouteKey('slug')]`
  no model.
- **`route('locations.edit', $location)`** faz o caminho inverso: recebe o model e põe o id na
  URL.
- **Comparação com o Laminas:** lá você pegaria `$this->params()->fromRoute('id')` e faria o
  `find()` com o 404 à mão; aqui o framework faz os dois.

## Comandos úteis

```bash
just artisan route:list --path=locations   # ver os parâmetros {location} de cada rota
```
