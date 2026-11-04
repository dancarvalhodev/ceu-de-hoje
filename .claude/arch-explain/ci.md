# CI (GitHub Actions) e Larastan

## O que é

A cada push e pull request, o GitHub roda `.github/workflows/ci.yml`: Pint, Larastan e Pest, em
passos separados. O Larastan é o PHPStan com as regras do Laravel (entende facades, models e
relações). Configuração em `phpstan.neon`, nível 5.

## Analogia

O **controle de qualidade na saída da fábrica**: toda peça (commit) passa pelas três bancadas
antes de seguir. Se uma reprova, o GitHub mostra qual e o resto do lote fica parado.

## Como funciona aqui

- **Sem Docker no CI.** O runner instala PHP 8.5 com o `shivammathur/setup-php`, com as mesmas
  extensões da imagem (menos o Xdebug). É mais rápido que construir a imagem a cada push.
- **Sem banco nem Redis.** O `phpunit.xml` já troca para SQLite em memória e drivers `array`.
  O `.env` do CI é o `.env.example` com uma `APP_KEY` nova, só para a criptografia de cookie e
  sessão funcionar.
- **Sem Node.** O `TestCase` chama `withoutVite()`, então o `@vite` do layout não procura o
  `public/build`. O `@fonts` sem manifesto já devolve vazio.
- **Larastan olha `app/`, `routes/` e `database/`.** Os testes ficam de fora: o Pest liga o
  `$this` ao `TestCase` em tempo de execução, e o PHPStan não enxerga isso.

## Pegadinhas

- **Rode `just cs`, `just stan` e `just test` antes do push**: são os mesmos comandos do CI.
- **Erro do Larastan sobre propriedade de model** (`$user->name` "não existe") se resolve com
  PHPDoc `@property` no model, não baixando o nível.
- **Passo `Pint` falhou no CI?** `just cs-fix` corrige e você commita o resultado.

## Comandos úteis

```bash
just stan                       # Larastan local
just composer audit             # vulnerabilidades conhecidas nas dependências
```
