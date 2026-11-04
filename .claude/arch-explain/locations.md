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

## Pegadinhas

- **Migração nova = arquivo novo.** Depois do `just migrate`, não edite esta migration; mudanças
  vão num `make:migration` novo.
- **`$user->locations` × `$user->locations()`:** sem parênteses é a coleção já carregada; com
  parênteses é a consulta (para `create()`, `where()`...).

## Comandos úteis

```bash
just artisan make:model Location -mf
just artisan migrate:status
```
