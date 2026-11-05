# Estilo e convenções

Siga o arquivo ao redor e o que o Laravel gera. Quando o skeleton e uma regra daqui discordarem,
pergunte.

## Clareza acima de esperteza

O código precisa ser fácil de ler para um dev júnior ou pleno, e para alguém que está aprendendo
Laravel agora.

- Uma variável nomeada por passo em vez de uma cadeia longa de chamadas.
- Um `if` simples em vez de ternários aninhados ou `?:`/`??` encadeados.
- Nada de `extract()`, de regex onde um laço basta ou de nomes que não dizem o que a coisa faz.
- Poucos comentários: só o porquê que o código não mostra. Regex sempre com um comentário breve.
- Tipos em parâmetros, retornos e propriedades. PHPDoc só quando acrescenta algo que a assinatura
  não diz (ex.: `@return Collection<int, Location>`, `@property` para o Larastan).
- Sem `declare(strict_types=1)`: o skeleton do Laravel não usa, e os arquivos gerados pelo
  `make:*` ficam iguais aos escritos à mão.

## O jeito Laravel

Diferente do Wecker (Slim, SQL à mão), aqui se usa o que o framework oferece:

| Necessidade | Use | Evite |
|---|---|---|
| Acesso a dados | Eloquent (models, relações, scopes) | Repository sobre o Eloquent, SQL cru |
| Validação | Form Request (`make:request`) | Validar dentro do controller |
| Autorização | Policy (`make:policy`) + `Gate::authorize()` ou middleware `can` | `if ($x->user_id !== auth()->id())` espalhado |
| Proteger rotas | Middleware `auth` e `guest` nos grupos de rotas | Checar sessão no controller |
| HTTP externo | `Http` client | Guzzle direto, `file_get_contents` |
| Cache | `Cache::remember()` | Redis à mão |
| Tarefa demorada ou agendada | Job + Scheduler (`routes/console.php`) | Cron chamando script |
| E-mail | Mailable + `Mail` | `mail()` |
| Dados de teste | Factories e seeders | Inserts em teste |

## Organização e nomes

- **Model** no singular (`Location`), tabela no plural em snake_case (`locations`), chave
  estrangeira `user_id`. Siga a convenção e não configure `$table`.
- **Mass assignment:** declare `$fillable` em todo model.
- **Controller** com sufixo `Controller`. Para CRUD, controller de recurso
  (`make:controller LocationController --resource`) e `Route::resource()`. Para uma ação só,
  controller invocável.
- **Rotas sempre nomeadas** (`->name('locations.store')`) e chamadas por `route()`, nunca por URL
  escrita à mão.
- **Form Request:** `StoreLocationRequest`, `UpdateLocationRequest`. Mensagens de validação em
  português no método `messages()` do próprio request.
- **Regra de negócio** em classes simples em `app/Services/` (`NightScore`, `ForecastService`),
  com dependências pelo construtor; o container do Laravel resolve sozinho.
- **Facades** (`Auth`, `Http`, `Cache`, `Mail`) podem ser usadas em controllers, jobs e testes.
  Classes de `app/Services/` recebem o que precisam pelo construtor.
- **Jobs** com sufixo `Job` e nome de ação (`CheckTonightJob`); **Mailables** com nome do e-mail
  (`GoodNightAlert`).

## Blade (até a etapa 4)

- Layout como componente (`resources/views/components/layout.blade.php`, usado como
  `<x-layout>`), como no curso.
- `@csrf` em todo formulário; `@method('PUT')`/`@method('DELETE')` para os verbos que o HTML não
  tem.
- `old()` para repovoar campos e `@error` para mostrar a mensagem do campo.
- `{{ }}` sempre; `{!! !!}` só com motivo escrito no relatório.

## React (a partir da etapa 5)

React com Inertia. Componentes funcionais com hooks (`useState`, `useEffect`...); nada de
componentes de classe. Páginas Inertia em `resources/js/Pages/`, componentes em
`resources/js/Components/`, hooks próprios em `resources/js/hooks/` com prefixo `use`
(`useVisiblePlanets`). Arquivos `.jsx`, um componente por arquivo, com nome em PascalCase.

## Testes

- Pest. Feature test para rota, controller e autorização; unit test para classes de
  `app/Services/`.
- `RefreshDatabase` e factories em todo teste que toca o banco.
- Nada de rede de verdade: `Http::fake()`, `Mail::fake()`, `Queue::fake()`.
- Teste o que importa: o comportamento (quem consegue, quem é barrado, o que é salvo), não que o
  framework funciona.

## Antes de entregar

Todo arquivo PHP tocado sai limpo no `pint --test` e no Larastan (depois de instalado), e os testes
passam.
