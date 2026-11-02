# Testes (Pest)

## O que é

[Pest](https://pestphp.com) é uma camada sobre o PHPUnit: os testes viram funções (`it()`,
`test()`) em vez de métodos de classe, e as asserções podem ser escritas com `expect()`. Por baixo
continua sendo PHPUnit, com o mesmo `phpunit.xml`.

## Analogia

O PHPUnit é um **formulário com campos fixos** (classe, método `test_...`, `$this->assert...`).
O Pest é o **mesmo formulário preenchido por voz**: você diz "ele retorna sucesso" e o Pest monta a
classe por trás.

## Como funciona aqui

- **`tests/Pest.php`** liga todo teste de `tests/Feature` ao `Tests\TestCase` do Laravel (que
  sobe a aplicação e dá `$this->get()`, `actingAs()`...) e ao trait `RefreshDatabase`. Os de
  `tests/Unit` ficam com o TestCase puro, sem Laravel: rápidos, para classes de `app/Services/`.
- **Banco dos testes:** o `phpunit.xml` troca o MySQL por SQLite em memória, sessão, cache e
  e-mail por drivers `array` e a fila por `sync` (o Job roda na hora). O `.env` de dev não é tocado; os testes não precisam do MySQL.
- **`RefreshDatabase`** roda as migrations uma vez e envolve cada teste numa transação desfeita
  no fim: cada teste começa com o banco vazio
  ([docs](https://laravel.com/docs/13.x/database-testing#resetting-the-database-after-each-test)).
- **`php artisan test`** detecta o Pest e o executa; é o que o `just test` roda.

## Pegadinhas

- **Variáveis do `phpunit.xml` vencem o `.env`.** Se um teste se comporta diferente do navegador,
  confira ali.
- **SQLite não é MySQL.** Diferenças pequenas (tipos, `ALTER`) podem aparecer; até aqui não
  importam para o CRUD do projeto.

## Comandos úteis

```bash
just test                   # tudo
just test "returns a"       # só os testes cujo nome contém o filtro
just artisan make:test LocationTest --pest           # feature test
just artisan make:test NightScoreTest --unit --pest  # unit test
```
