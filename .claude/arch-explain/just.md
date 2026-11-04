# just (o `justfile`)

## O que é

[just](https://just.systems) é um executor de comandos: lê o `justfile` da raiz e roda receitas
pelo nome (`just up`, `just test`). Aqui ele esconde o `docker compose exec php ...` de cada
comando. Segue o modelo do [Wecker](https://github.com/dancarvalhodev/wecker), outro projeto do
dev; a explicação completa da sintaxe do `just` está no
[`just.md` do Wecker](https://github.com/dancarvalhodev/wecker/blob/master/.claude/arch-explain/just.md).

## Analogia

O **cardápio de um restaurante**: cada receita é um prato com descrição de uma linha;
`just` sozinho mostra o cardápio; as receitas com `_` são a cozinha, que confere gás e
ingrediente (`_docker`, `_env`, `_running`) antes de servir.

## Como funciona aqui

| Grupo | Receitas |
|---|---|
| environment | `install`, `setup`, `up`, `stop`, `status`, `logs`, `shell [serviço]` |
| laravel | `artisan <args>`, `migrate`, `fresh` (confirma), `tinker` |
| php | `composer <args>`, `test [filtro]`, `stan`, `cs`, `cs-fix` |
| frontend | `npm <args>`, `vite` |
| cleanup | `reset` (confirma) |

- **`install`** é uma fila de dependências, em ordem: `setup` → `up` → `composer install` →
  `_key-if-missing` → `migrate` → `npm install` → `npm run build`. Se uma falha, o resto não roda.
- **`_key-if-missing`** só roda o `key:generate` se a `APP_KEY` estiver vazia: o comando sempre
  sobrescreve, e trocar a chave derruba as sessões e invalida o que foi criptografado.
- **PHP com `exec`, Node com `run --rm`.** O `php` fica sempre de pé, então as receitas entram
  nele (`exec`). O `node` não sobe no `up`: cada `just npm` cria um container descartável.
  `just vite` usa `--service-ports` para publicar a porta 5173.
- **Como `www-data`, não root.** As receitas do `php` entram com `exec -u www-data` (e o `shell`
  também, quando o serviço é o `php`); o `HOST_UID`/`HOST_GID` do topo do `justfile` faz esse
  usuário ter o seu uid. Detalhes em `infra.md`.
- **`test`, `cs` e `stan`** rodam `php artisan test` (Pest), `pint --test` e `phpstan analyse`.

## Pegadinhas

- **`just stan`** só funciona depois que o Larastan for instalado (etapa 8 do plano).
- **`--yes` pula o `[confirm]`.** `just --yes fresh` apaga o banco sem perguntar.
- **`reset` apaga os volumes** deste projeto (banco e Redis), não o Docker da máquina.
- **Cada linha do corpo é um shell novo.** Para estado entre linhas, use o modo shebang (`setup`).

## Comandos úteis

```bash
just                       # cardápio
just --show install        # código de uma receita
just --dry-run install     # o que rodaria, sem rodar
just test Location         # só os testes com "Location" no nome
```
