# Pontuação de observabilidade (SkyScore)

## O que é

Uma nota de 0 a 10 que diz se o céu está bom para observar **agora**, no local do usuário. Junta
dois fatores: a cobertura de nuvens do momento (Open-Meteo, via `ForecastService`) e a iluminação
da lua (pacote `solaris/php-moon-phase`, via `MoonPhase`).

## Analogia

Pense numa janela com duas cortinas. As **nuvens** são uma cortina grossa: fechada, não se vê
nada. A **lua** é uma cortina fina: mesmo toda fechada, ainda deixa ver metade das estrelas,
porque o brilho dela apaga só as mais fracas.

## Como funciona aqui

A pontuação começa em 10 e perde por dois motivos:

- **Nuvens** tiram na mesma proporção: 30% de nuvens deixam 70% da pontuação; céu fechado dá 0.
- **Lua** tira até metade: nova não tira nada, meia-lua tira um quarto, cheia tira metade.

```
pontuação = 10 × (1 − nuvens / 100) × (1 − 0,5 × iluminação)
```

Arredondada a uma casa decimal.

| Céu | Lua | Pontuação |
|---|---|---|
| Limpo | Nova | 10 |
| Limpo | Cheia | 5 |
| Metade nublado | Nova | 5 |
| Metade nublado | Cheia | 2,5 |
| Fechado | Qualquer | 0 |

**No código:**

- `app/Services/SkyScore.php`: `calculate(int $cloudCover, float $moonIllumination): float`. Só
  a conta; não busca nada.
- Testes: `tests/Unit/SkyScoreTest.php`, com a tabela acima como dataset.

### A tela "Céu agora"

- **Rota:** `GET /sky` (nome `sky`), no grupo `auth` de `routes/web.php`.
- **`SkyController`** é um [controller invocável](https://laravel.com/docs/13.x/controllers#single-action-controllers)
  (só `__invoke`), gerado com `make:controller SkyController --invokable`. Ele junta as peças:
  nuvens do `ForecastService`, iluminação e nome do `MoonPhase`, pontuação do `SkyScore`.
- **Injeção no método:** os três serviços chegam como parâmetros do `__invoke`. O container lê os
  tipos e entrega as instâncias, como no construtor
  ([method injection](https://laravel.com/docs/13.x/controllers#method-injection)).
- **Qual local:** sem `?location=`, o primeiro em ordem alfabética. Com ele,
  `$request->user()->locations()->findOrFail($id)` busca **só entre os locais do usuário**: id de
  outro usuário não é encontrado e vira 404, sem precisar da Policy.
- **Sem locais:** a view mostra um aviso com link para cadastrar, e nada é pedido à API.
- Testes: `tests/Feature/SkyTest.php`, com `Http::fake` (nuvens 0%) e `travelTo` numa lua cheia
  conhecida, para a pontuação dar sempre 5,0.

## Pegadinhas

- **Lua acima ou abaixo do horizonte não entra.** Uma lua cheia que ainda não nasceu não
  atrapalha, mas a pontuação desconta mesmo assim. Simplificação aceita.
- **Unit test sem Laravel:** `SkyScore` não tem dependências, então o teste faz `new SkyScore`
  em `tests/Unit`, sem subir o app.

## Comandos úteis

```bash
just test SkyScore
just tinker
> (new App\Services\SkyScore)->calculate(30, 0.5)   // 5.3
```
