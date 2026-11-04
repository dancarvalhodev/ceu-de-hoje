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
| `GET /login` | `login` | `guest` | `Route::view` → `auth/login.blade.php` |
| `POST /login` | `login.store` | `guest` | `Auth\LoginController` (invocável) |
| `POST /logout` | `logout` | `auth` | `Auth\LogoutController` (invocável) |

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
- **Login com `Auth::attempt($credentials)`:** busca o usuário pelo e-mail e confere a senha
  contra o hash. Falhou? Volta com a mesma mensagem para e-mail inexistente e senha errada, para
  não revelar quem tem conta.
- **`redirect()->intended(...)`:** quem foi barrado em `/locations` e mandado ao login volta para
  `/locations`; quem abriu o login direto cai no destino padrão.
- **Logout:** `Auth::logout()`, `session()->invalidate()` (apaga a sessão) e
  `regenerateToken()` (novo token CSRF). É `POST` com `@csrf`, para que um link externo não
  derrube a sessão de ninguém.
- **`@guest` / `@auth`** no `<x-layout>` decidem o menu.

## Pegadinhas

- **Para onde os middlewares mandam:** o `auth` manda visitante para a rota de nome `login`, e o
  `guest` manda quem está logado para a rota `home`. É por convenção de nome: renomear essas
  rotas quebra os redirecionamentos (configuráveis em `bootstrap/app.php`, com
  `redirectGuestsTo` e `redirectUsersTo`).
- **`email` com `lowercase`:** a regra recusa maiúsculas em vez de convertê-las.

## Comandos úteis

```bash
just artisan route:list --except-vendor      # rotas do app, com nome
just artisan route:list --except-vendor -v   # o mesmo, com os middlewares
```
