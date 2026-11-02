# Vite (CSS e JS)

## O que é

[Vite](https://vite.dev) é a ferramenta que **transforma o CSS e o JS de `resources/` nos arquivos
que o navegador carrega**. O Laravel já vem integrado a ele pelo `laravel-vite-plugin` e pela
diretiva `@vite` do Blade ([docs](https://laravel.com/docs/13.x/vite)).

No Laminas você provavelmente punha um `style.css` pronto em `public/` e um `<link>` no layout.
Funciona enquanto o CSS é escrito à mão. Aqui o CSS é gerado: o Tailwind lê as classes usadas nas
views e produz só o CSS delas. Na etapa 5, os arquivos `.vue` também precisam ser compilados para
JS. Alguém tem de fazer essa tradução: é o Vite.

## Analogia

Uma **cozinha de restaurante com dois modos**:

- **Durante o preparo (`just vite`):** o cozinheiro fica ao lado da mesa. Você muda um tempero
  (edita uma view ou o CSS) e o prato é ajustado na hora, sem trazer outro: a página se atualiza
  sozinha (*hot reload*).
- **Para o serviço (`npm run build`):** os pratos saem prontos e embalados em `public/build`,
  com uma etiqueta (o `manifest.json`) dizendo qual embalagem é qual. Não precisa de cozinheiro
  ligado.

## Como funciona aqui

- **Entrada:** `resources/css/app.css` (com o Tailwind 4) e `resources/js/app.js`, listados no
  `vite.config.js`.
- **No layout:** `@vite(['resources/css/app.css', 'resources/js/app.js'])`. A diretiva decide
  sozinha de onde carregar:
  - se existe `public/hot` (criado enquanto o `just vite` roda), aponta para
    `http://localhost:5173`, o servidor de desenvolvimento;
  - senão, lê o `public/build/manifest.json` e aponta para os arquivos compilados, com nomes como
    `app-3f2a9c.css` (o hash muda a cada build, então o navegador nunca usa um cache velho).
- **Em container:** o Vite roda no serviço `node`. O `vite.config.js` escuta em `0.0.0.0:5173`
  para ser alcançado de fora do container e anuncia `localhost` ao navegador (`hmr.host`).
- **`refresh: true`:** recarrega a página quando uma view Blade muda, não só o CSS ou o JS.
- **Quando usar cada um:**
  - mexendo em tela: `just vite` aberto num terminal;
  - só no PHP, sem mudar classes: o build do `just install` basta;
  - mudou classes do Tailwind e não quer o `just vite`: `just npm run build`.

## Pegadinhas

- **Classe do Tailwind nova sem efeito?** Sem o `just vite` rodando, o CSS só é gerado de novo com
  `just npm run build`.
- **Página sem estilo e erros para `localhost:5173`:** o `just vite` foi encerrado de forma
  abrupta e deixou o `public/hot` para trás. Apague o arquivo ou rode `just vite` de novo.
- **Testes não precisam do Vite.** Quando o layout usar `@vite`, os testes chamam
  `withoutVite()` (etapa 8), para não depender do `public/build` no CI.
- **Aviso do `fontaine` ao subir:** o plugin de fontes do skeleton sugere um pacote opcional.
  Não afeta nada.

## Comandos úteis

```bash
just vite              # servidor de desenvolvimento com hot reload (Ctrl+C para parar)
just npm run build     # gera public/build
just npm install       # depois de mudar o package.json
```
