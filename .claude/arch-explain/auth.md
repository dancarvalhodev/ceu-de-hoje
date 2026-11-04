# Autenticação (registro, login e logout)

## O que é

Login feito à mão, sem starter kit, como no curso Getting Started with Laravel. O Laravel dá as
peças (guard de sessão, `Auth`, middlewares `auth` e `guest`, hash de senha); as rotas, os
controllers e as views são nossos.

## Analogia

A **portaria de um prédio**. O registro é o cadastro do morador, que já sai com o crachá na mão
(`Auth::login`). O middleware `auth` é o porteiro que barra quem não tem crachá; o `guest` manda
de volta quem já entrou e tenta se cadastrar de novo.

## Como funciona aqui

| Rota | Nome | Middleware | Quem atende |
|---|---|---|---|
| `GET /register` | `register` | `guest` | `Route::view` → `auth/register.blade.php` |
| `POST /register` | `register.store` | `guest` | `Auth\RegisterController` (invocável) |

- **Validação no `RegisterRequest`** ([Form Request](https://laravel.com/docs/13.x/validation#form-request-validation)):
  o Laravel valida antes de chamar o controller. Se falhar, volta para o formulário com os erros
  (`@error`) e os campos preenchidos (`old()`). Mensagens em português no `messages()`.
- **Senha sem `Hash::make` no controller:** o model `User` tem o cast `'password' => 'hashed'`,
  que gera o hash ao salvar.
- **`User::create($request->validated())`:** só os campos validados chegam ao model, e o
  `#[Fillable]` do `User` limita o que pode ser preenchido em massa
  ([mass assignment](https://laravel.com/docs/13.x/eloquent#mass-assignment)). O
  `password_confirmation` não entra no `validated()`.
- **`Auth::login($user)` + `session()->regenerate()`:** entra com o usuário novo e troca o id da
  sessão, contra fixação de sessão.
- **`@guest` / `@auth`** no `<x-layout>` decidem o menu.

## Pegadinhas

- **Visitante em rota `auth` é mandado para `route('login')`.** Enquanto a rota `login` não
  existir (etapa 7), abrir `/locations` deslogado dá erro 500.
- **`email` com `lowercase`:** a regra recusa maiúsculas em vez de convertê-las.

## Comandos úteis

```bash
just artisan route:list --except-vendor      # rotas do app, com nome
just artisan route:list --except-vendor -v   # o mesmo, com os middlewares
```
