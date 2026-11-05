# Plano: botão "Usar minha localização"

## Objetivo

Facilitar o cadastro e os testes de local: um botão no formulário que preenche latitude e
longitude pela localização do navegador.

Pronto quando: o botão preenche os dois campos no navegador; Pint, Larastan e Pest verdes.

## Ponto de partida

- Coordenadas: coluna `decimal(8, 6)` / `decimal(9, 6)` e cast `float` no model. Ficam como estão.
- O formulário (`resources/views/locations/_form.blade.php`) usa `<input type="number"
  step="any">`. Com o navegador em português, o decimal é digitado com vírgula (`-23,55`); o
  navegador envia com ponto e o valor é salvo com as casas decimais.
- `resources/js/app.js` já é carregado pelo Vite em todas as páginas (`<x-layout>`).

## Decisões do dev

1. **Geolocalização agora, em JS puro**, para facilitar os testes. É uma exceção à regra "sem JS
   antes da etapa 5": só a API nativa do navegador, sem biblioteca nem framework.
2. **MVP:** só latitude e longitude. O fuso continua manual (padrão `America/Sao_Paulo`).

## Etapas de implementação

1. **Botão "Usar minha localização".** Arquivo `resources/js/geolocation.js`, importado no
   `resources/js/app.js`. Botão `type="button"` no `_form.blade.php`; ao clicar,
   `navigator.geolocation.getCurrentPosition()` preenche latitude e longitude com 6 casas.
   Mensagem em português se o usuário negar ou o navegador não tiver suporte. Feature test: o
   formulário mostra o botão. O JS o dev testa no navegador (não há teste de JS no projeto).
   Registrar a exceção à regra do JS no `CLAUDE.md` e no `docs/PROJETO.md`, e criar
   `.claude/arch-explain/geolocation.md`.

## Fora deste plano

- Preencher o fuso pelo navegador, mapa para escolher o ponto, busca por endereço.
- Teste automatizado de JS (entra com o React, na etapa 5).

## Progresso

- [x] 1. Botão "Usar minha localização" — confirmada em 2026-11-05
