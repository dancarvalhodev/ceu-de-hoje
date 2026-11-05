# Geolocalização (botão "Usar minha localização")

## O que é

Um botão no formulário de local que preenche latitude e longitude com a posição do navegador,
pela [Geolocation API](https://developer.mozilla.org/pt-BR/docs/Web/API/Geolocation_API), nativa
do navegador. Não é recurso do Laravel nem biblioteca: é JS puro.

## Analogia

É como pedir o endereço a um amigo pelo celular em vez de procurar no mapa: o navegador pergunta
"este site quer saber sua localização, permitir?", e se você deixar, ele responde com as
coordenadas. O Laravel só recebe o formulário já preenchido, como se você tivesse digitado.

## Como funciona aqui

- **Arquivo:** `resources/js/geolocation.js`, importado no `resources/js/app.js`. O `@vite` do
  `<x-layout>` já carrega o `app.js` em todas as páginas (ver `vite.md`).
- **Ligação com o HTML:** o script procura o botão por `data-geolocation` e escreve a mensagem
  no elemento `data-geolocation-status` (ambos no `_form.blade.php`, usado por criar e editar).
  Se a página não tem o botão, o script não faz nada.
- **O clique:** `navigator.geolocation.getCurrentPosition()` pede a permissão e devolve
  `coords.latitude` e `coords.longitude`, gravados nos campos `#latitude` e `#longitude` com 6
  casas (a precisão da coluna `decimal`).
- **Servidor:** nada muda. O Form Request valida os valores como se tivessem sido digitados.
- **Exceção à regra do projeto:** é o único JS antes da etapa 5 (registrado no `CLAUDE.md` e no
  `docs/PROJETO.md`). Na etapa 5, vira parte do componente React do formulário.

## Pegadinhas

- **Só em `localhost` ou HTTPS.** Em HTTP comum (fora do `localhost`), o navegador bloqueia a API.
  No deploy (etapa 4), o site precisa estar em HTTPS.
- **Permissão negada fica lembrada.** Se você clicou "Bloquear", o navegador não pergunta de novo:
  libere no cadeado ao lado do endereço.
- **Campo `number` e vírgula:** o JS grava o valor com ponto (`-23.550000`) direto na propriedade
  `value`, que sempre usa ponto; o navegador mostra no formato local. Não há conflito.
- **Precisão no desktop:** sem GPS, o navegador estima pela rede (Wi-Fi ou IP). Pode errar por
  alguns quilômetros, o que não muda as nuvens nem a pontuação.
- **JS mudou e nada aconteceu:** sem o `just vite` rodando, gere de novo com `just npm run build`.

## Comandos úteis

```bash
just vite              # hot reload enquanto mexe no JS
just npm run build     # gera public/build sem o servidor do Vite
```
