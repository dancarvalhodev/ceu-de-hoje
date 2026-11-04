# Locais de observação (`Location`)

## O que é

O lugar de onde o usuário observa o céu: nome, latitude, longitude e fuso (IANA, ex.:
`America/Sao_Paulo`). Um usuário tem vários locais (`hasMany`); cada local pertence a um usuário.

## Analogia

Os **endereços salvos num app de entrega**: cada conta tem os seus, e apagar a conta apaga a
lista junto.

## Como funciona aqui

- **Gerado com `just artisan make:model Location -mf`:** model, migration (`-m`) e factory (`-f`)
  de uma vez.
- **Convenções do Eloquent** ([docs](https://laravel.com/docs/13.x/eloquent#eloquent-model-conventions)):
  model `Location` → tabela `locations`; `$this->belongsTo(User::class)` procura a coluna
  `user_id`; `hasMany(Location::class)` no `User` procura `locations.user_id`. Nada disso é
  configurado.
- **Migration:** `foreignId('user_id')->constrained()->cascadeOnDelete()` cria a coluna, a chave
  estrangeira para `users.id` (deduzida do nome) e o `ON DELETE CASCADE`. Latitude
  `decimal(8, 6)` e longitude `decimal(9, 6)`: 6 casas são uns 11 cm.
- **Casts `float`:** o MySQL devolve `decimal` como string; o cast entrega número ao PHP (e, mais
  adiante, à chamada do Open-Meteo).
- **`#[Fillable]`** (atributo do Laravel 13, igual ao do `User`) lista o que pode entrar por
  `create()`/`update()`. O `user_id` fica de fora de propósito: vem sempre do usuário logado, via
  relação (`$user->locations()->create(...)`), nunca do formulário.
- **Factory:** `user_id => User::factory()` cria um dono quando o teste não informa um;
  `->for($user)` usa um dono existente.

### Cadastro (CRUD)

- **`Route::resource('locations', ...)->except('show')`** cria as 6 rotas nomeadas
  (`locations.index`, `.create`, `.store`, `.edit`, `.update`, `.destroy`) apontando para os
  métodos de mesmo nome do `LocationController` (gerado com `make:controller --resource
  --model=Location`). Veja com `just artisan route:list`.
- **Route model binding:** em `edit(Location $location)`, o `{location}` da URL vira o model
  buscado por id; id inexistente dá 404 sozinho. Detalhes em `route-model-binding.md`.
- **`$request->user()->locations()->create(...)`** grava já com o `user_id` do logado.
- **`UpdateLocationRequest` herda do `StoreLocationRequest`:** mesmas regras e mensagens.
- **Um formulário, duas telas:** `locations/_form.blade.php` entra por `@include` no `create` e no
  `edit`. `old('name', $location->name)` mostra o que foi digitado (se a validação falhou) ou o
  valor salvo. No `create`, o `$location` é um model novo, não salvo, com o fuso
  `America/Sao_Paulo` já escolhido.
- **`@method('PUT')` / `@method('DELETE')`:** o HTML só envia GET e POST; o campo oculto
  `_method` diz ao Laravel qual verbo usar.
- **Mensagem de sucesso:** `redirect()->with('status', ...)` grava na sessão só para a próxima
  requisição (flash); o `<x-layout>` mostra `session('status')`.

## Pegadinhas

- **Fuso padrão `America/Sao_Paulo` (decisão do dev):** acerta para a maior parte do público.
  Quem mora em outro fuso precisa trocar; se não trocar, a "noite" é calculada no horário
  errado. Detectar o fuso do navegador fica como ideia para a etapa 5, quando entra o JS.

- **Editar e excluir passam pela `LocationPolicy`** (`Gate::authorize`): local de outro usuário
  dá 403. Detalhes em `authorization.md`.

- **Migração nova = arquivo novo.** Depois do `just migrate`, não edite esta migration; mudanças
  vão num `make:migration` novo.
- **`$user->locations` × `$user->locations()`:** sem parênteses é a coleção já carregada; com
  parênteses é a consulta (para `create()`, `where()`...).

## Comandos úteis

```bash
just artisan make:model Location -mf
just artisan migrate:status
```
