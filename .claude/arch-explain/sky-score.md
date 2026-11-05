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
- Quem junta as peças é o controller da tela "Céu agora" (etapa 6): pede as nuvens ao
  `ForecastService`, a iluminação ao `MoonPhase` e passa as duas ao `SkyScore`.
- Testes: `tests/Unit/SkyScoreTest.php`, com a tabela acima como dataset.

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
